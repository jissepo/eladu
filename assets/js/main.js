import Vue from "vue";
// import VCalendar from "v-calendar";
import axios from "axios";
import VueAxios from "vue-axios";
import vSelect from "vue-select";
import HotelDatePicker from "vue-hotel-datepicker";
import checkView from "vue-check-view";
import VueTippy, { TippyComponent } from "vue-tippy";
import Sticky from "vue-sticky-directive";

import moment from "moment";
import Qs from "qs";
import * as EmailValidator from "email-validator";

Vue.use(VueTippy);
Vue.use(checkView);
Vue.use(VueAxios, axios);
Vue.use(Sticky);

Vue.component("tippy", TippyComponent);
Vue.component("v-select", vSelect);
Vue.component("v-date-picker", HotelDatePicker);

Vue.filter("formatDate", function (value) {
  if (value) {
    return moment(String(value)).format("DD.MM.YYYY");
  }
});
Vue.filter("formatSum", function (value) {
  if (value !== null) {
    return php_object.sum_template.replace(
      "0.00",
      parseFloat(value).toFixed(2)
    );
  }
});
new Vue({
  el: "#vue--checkout",
  data: {
    canShowContact: false,
    highestStep: 0,
    countries: [],
    loader: {
      isLoading: true,
      isFixedTop: false,
      isFixedBottom: false,
      styles: {
        position: "fixed",
        bottom: "initial",
        top: "initial",
      },
    },
    location: {
      locations: [],
      displayIframe: false,
    },
    extras: {
      // available: [],
      selected: [],
    },
    datepicker: {
      locale: php_object.translations.datepicker,
      isInfinite: false,
      minNights: 31,
      checkIn: null,
      checkOut: null,
    },
    mobile: {
      validated: false,
      code: null,
      error: "",
    },
    checkout: {
      errors: [],
      type: "private",
      country: null,
      method: null,
      location: null,
      box: null,
      privacyPolicy: false,
      fields: {
        mobile: "+372",
        firstName: null,
        lastName: null,
        identifierCode: null,
        representativeFirstName: null,
        representativeLastName: null,
        companyName: null,
        registryCode: null,
        email: null,
        address: null,
        postcode: null,
        jurisdiction: null,
        country: null,
      },
    },
  },
  computed: {
    currentStep() {
      if (
        this.highestStep === 3 ||
        (this.checkout.box && this.mobile.validated)
      ) {
        this.highestStep = 3;
        return 3;
      }
      if (this.highestStep === 2 || this.checkout.box || this.canShowContact) {
        this.highestStep = 2;
        return 2;
      }
      return 1;
    },
    totalSum() {
      let sum = 0;
      if (this.checkout.box) {
        sum += parseFloat(this.checkout.box.price, 10);
      }
      if (this.extras.selected.length) {
        sum += this.extras.selected.reduce(
          (accumulator, extra) => accumulator + parseFloat(extra.price),
          0
        );
      }
      return sum;
    },
    errorMessage() {
      if (!this.datepicker.value) return "Kuupäev on kohustuslik";
      return "";
    },
    selectedLocationHref() {
      if (this.checkout.location && this.checkout.location.href) {
        return this.checkout.location.href;
      }

      return "";
    },
    selectedLocationLabel() {
      if (this.checkout.location && this.checkout.location.label) {
        return this.checkout.location.label;
      }

      return "Esmalt vali asukoht";
    },
    checkoutErrors() {
      const errors = [];

      if (!this.checkout.privacyPolicy) {
        errors.push("Peate nõustuma tingimustega.");
      }
      if (!this.datepicker.checkIn) {
        errors.push("Algusekuupäev peab olema valitud.");
      }
      if (!this.datepicker.checkOut && !this.datepicker.isInfinite) {
        errors.push("Lõppkuupäev peab olema valitud.");
      }
      if (!this.checkout.location) {
        errors.push("Aadress peab olema valitud.");
      }
      if (!this.checkout.box) {
        errors.push("Ladu peab olema valitud.");
      }
      if (!this.checkout.fields.mobile) {
        errors.push("Mobiili number peab olema sisestatud.");
      }
      if (!this.mobile.validated) {
        errors.push("Mobiili number peab olema valideeritud.");
      }

      if (this.checkout.type === "private") {
        if (!this.checkout.fields.firstName) {
          errors.push("Eesnimi peab olema sisestatud.");
        }
        if (!this.checkout.fields.lastName) {
          errors.push("Perekonnanimi peab olema sisestatud.");
        }
        if (!this.checkout.fields.identifierCode) {
          errors.push("Isikukood peab olema sisestatud.");
        }
      }
      if (this.checkout.type === "commercial") {
        if (!this.checkout.fields.representativeFirstName) {
          errors.push("Esindaja eesnimi peab olema sisestatud.");
        }
        if (!this.checkout.fields.representativeLastName) {
          errors.push("Esindaja perekonnanimi peab olema sisestatud.");
        }
        if (!this.checkout.fields.companyName) {
          errors.push("Ettevõtte nimi peab olema sisestatud.");
        }
        if (!this.checkout.fields.registryCode) {
          errors.push("Registrikood peab olema sisestatud.");
        }
      }
      if (!this.checkout.fields.email) {
        errors.push("E-mail peab olema sisestatud.");
      }
      if (
        this.checkout.fields.email &&
        !EmailValidator.validate(this.checkout.fields.email.trim())
      ) {
        errors.push("Palun kontrollige üle e-mail.");
      }
      if (!this.checkout.fields.address) {
        errors.push("Aadress peab olema sisestatud.");
      }
      if (!this.checkout.fields.jurisdiction) {
        errors.push("Piirkond peab olema sisestatud.");
      }
      if (!this.checkout.fields.country) {
        errors.push("Riik peab olema valitud.");
      }

      return errors;
    },
    datepickerEndDate() {
      if (this.datepicker.checkIn) {
        return new Date(8640000000000000);
      }
      return new Date(Date.now() + 12096e5);
    },
  },
  watch: {
    "checkout.location": function (newVal, oldVal) {
      this.highestStep = this.highestStep === 0 ? 1 : this.highestStep;
      this.updateAvailableBoxes(false);
    },
    "datepicker.checkIn": function (newVal, oldVal) {
      this.updateAvailableBoxes(true);
    },
    "datepicker.checkOut": function (newVal, oldVal) {
      this.updateAvailableBoxes(true);
    },
    "checkout.box": function (newVal, oldVal) {
      if (!this.canShowContact) {
        this.canShowContact = true;
      }
      if (this.checkout.box === null) {
        this.extras.selected = [];
      }
    },
    "mobile.validated": function (newVal, oldVal) {
      const self = this;
      axios
        .get(`${php_object.ajax_url}?action=get_makecommerce_data`)
        .then(function (response) {
          if (
            document.querySelector(
              ".confirmation__payment--single-makecommerce"
            )
          ) {
            // Set makecommerce html
            document.querySelector(
              ".confirmation__payment--single-makecommerce"
            ).innerHTML = response.data;
            // Select payment method
            document.querySelector("input#payment_method_makecommerce").click();
            document.querySelector(
              'label[for="payment_method_makecommerce"]'
            ).style.display = "none";
            // Show country picker
            document.querySelector(
              ".payment_box.payment_method_makecommerce"
            ).style.display = "block";
            document.querySelector(
              ".makecommerce-picker-country"
            ).style.display = "list-item";

            // Set logosize
            document
              .querySelectorAll(".makecommerce_country_picker_methods")
              .forEach((element) => {
                element.classList.add("logosize-medium");
              });

            // Show correct payment methods
            const eePicker = document.querySelector(
              "#makecommerce_country_picker_methods_ee"
            ).parentElement;
            eePicker.style.display = "list-item";
            let nextPicker = eePicker.nextElementSibling;
            while (nextPicker !== null) {
              nextPicker.style.display = "none";
              nextPicker = nextPicker.nextElementSibling;
            }

            //Country select
            document
              .querySelectorAll(".makecommerce_country_picker_label")
              .forEach((element) => {
                element.addEventListener("click", (event) => {
                  event.preventDefault();

                  const siblingLabels = Array.prototype.filter.call(
                    event.target.parentNode.children,
                    function (child) {
                      return (
                        child !== event.target && child.nodeName === "LABEL"
                      );
                    }
                  );
                  event.target.classList.add("selected");
                  siblingLabels.forEach((sibling) => {
                    sibling.classList.remove("selected");
                  });
                  document
                    .querySelectorAll(".makecommerce-banklink-picker")
                    .forEach((pickerElement) => {
                      pickerElement.classList.remove("selected");
                    });

                  const selectedCountryPickerContainer = document.querySelector(
                    `#makecommerce_country_picker_methods_${event.target.previousElementSibling.value}`
                  ).parentElement;

                  selectedCountryPickerContainer.style.display = "list-item";

                  const siblings = Array.prototype.filter.call(
                    selectedCountryPickerContainer.parentNode.children,
                    function (child) {
                      return (
                        child !== selectedCountryPickerContainer &&
                        child.nodeName === "LI"
                      );
                    }
                  );

                  siblings.forEach((sibling) => {
                    sibling.style.display = "none";
                  });

                  self.checkout.country =
                    event.target.previousElementSibling.value;
                  self.checkout.method = null;

                  selectedCountryPickerContainer
                    .querySelector(".makecommerce-banklink-picker")
                    .click();
                });
              });

            // Method select
            document
              .querySelectorAll(".makecommerce-banklink-picker")
              .forEach((pickerElement) => {
                pickerElement.addEventListener("click", (event) => {
                  event.preventDefault();
                  event.currentTarget.classList.add("selected");
                  const siblingPickers = Array.prototype.filter.call(
                    event.currentTarget.parentNode.children,
                    function (child) {
                      return (
                        child !== event.currentTarget &&
                        child.classList.contains("makecommerce-banklink-picker")
                      );
                    }
                  );
                  siblingPickers.forEach((sibling) => {
                    sibling.classList.remove("selected");
                  });
                  self.checkout.method = event.currentTarget.getAttribute(
                    "banklink_id"
                  );
                });
              });

            // Defaults selecting
            document
              .querySelector('label[for="makecommerce_country_picker_ee"]')
              .click();
            document.querySelector(".makecommerce-banklink-picker").click();
          }
        })
        .catch(function (error) {
          // handle error
          console.log(error);
        });
    },
  },
  created: async function () {
    const self = this;
    this.axios
      .get(`${php_object.ajax_url}?action=get_storage_locations`)
      .then(function (response) {
        if (response.data.success) {
          self.location.locations = response.data.data;
        }
        self.loader.isLoading = false;
      })
      .catch(function (error) {
        console.log(error);
        self.loader.isLoading = false;
      });
  },
  mounted: function () {
    const self = this;
    this.axios
      .get(`${php_object.ajax_url}?action=get_available_countries`)
      .then(function (response) {
        if (response.data.success) {
          self.countries = response.data.data;
        } else {
          console.error(response.data);
        }
      })
      .catch(function (error) {
        console.log(error);
      });
  },
  methods: {
    loaderScrollHandler: function (event) {
      const isFixedTop = event.percentTop > 0.6;
      const isFixedBottom = event.percentTop < 0.4;

      if (isFixedTop || isFixedBottom) {
        const parentPos = event.target.element.getBoundingClientRect(),
          childPos = event.target.element.firstChild.getBoundingClientRect();

        const top = childPos.top - parentPos.top;
        this.loader.styles.top = `${top}px`;
        this.loader.styles.position = `absolute`;
      } else {
        this.loader.styles.top = `50vh`;
        this.loader.styles.position = `fixed`;
      }
    },
    changeDatePickerMode: function (event) {
      this.datepicker.isInfinite = event.target.checked;

      if (this.datepicker.isInfinite) {
        this.datepicker.minNights = 999;
        this.datepicker.checkOut = null;
      } else {
        this.datepicker.minNights = 31;
      }
    },
    updateCheckInDate: function (event) {
      this.datepicker.checkOut = null;
      this.datepicker.checkIn = event;
      this.highestStep = this.highestStep === 0 ? 1 : this.highestStep;
    },
    changeSelectedExtra: function (event, extra) {
      // TODO: Validate that this actually works when products are in.
      if (event.target.checked) {
        this.extras.selected.push(extra);
      } else {
        this.extras.selected = this.extras.selected.filter(
          (indexExtra) => indexExtra.id !== extra.id
        );
      }
    },

    sendValidationCode: function () {
      const self = this;
      self.loader.isLoading = true;
      this.axios
        .get(`${php_object.ajax_url}?action=send_verification_code`, {
          params: {
            phone_nr: this.checkout.fields.mobile,
          },
        })
        .then(function (response) {
          if (response.data.success) {
            if (response.data.data) {
              self.mobile.success = response.data.data.message;
              if (response.data.data.verify) {
                self.mobile.code = response.data.data.verify;
              }
            }
            self.mobile.error = null;
          } else {
            self.mobile.validated = false;
            self.mobile.error = response.data.data;
          }
          self.loader.isLoading = false;
        })
        .catch(function (error) {
          console.log(error);
          self.loader.isLoading = false;
        });
    },
    getSelectedLocationIndex() {
      return this.location.locations.findIndex(
        (storageLocation) =>
          this.checkout.location &&
          storageLocation.value === this.checkout.location.value
      );
    },

    locationHasBoxes() {
      if (this.getSelectedLocationIndex() === -1) {
        return false;
      }
      return (
        this.location.locations[this.getSelectedLocationIndex()].boxes !==
        undefined
      );
    },
    addLocationBoxes(boxes) {
      this.location.locations[this.getSelectedLocationIndex()].boxes = boxes;
    },
    validatePhone: function () {
      const self = this;
      self.loader.isLoading = true;
      this.axios
        .get(`${php_object.ajax_url}?action=validate_phone`, {
          params: {
            phone_nr: this.checkout.fields.mobile,
            code: this.mobile.code,
          },
        })
        .then(function (response) {
          if (response.data.success) {
            self.mobile.validated = true;
          } else {
            self.mobile.validated = false;
            self.mobile.error = response.data.data;
          }
          self.loader.isLoading = false;
        })
        .catch(function (error) {
          console.log(error);
          self.loader.isLoading = false;
        });
    },

    submitPurchase: function () {
      const self = this;
      self.loader.isLoading = true;
      const data = {
        checkIn: this.datepicker.checkIn,
        checkOut: this.datepicker.checkOut,
        location: this.checkout.location.value,
        box: this.checkout.box.value,
        order_comments: "",
        billing_country: this.checkout.country.code,
        billing_address_1: this.checkout.fields.address,
        billing_postcode: this.checkout.fields.postcode,
        billing_city: this.checkout.fields.jurisdiction,
        billing_state: "",
        billing_phone: this.checkout.fields.mobile,
        billing_email: this.checkout.fields.email,
        lang: "et",
        payment_method: "makecommerce",
        PRESELECTED_METHOD_makecommerce: this.checkout.method,
        makecommerce_country_picker: this.checkout.country,
        terms: "on",
        "terms-field": this.checkout.privacyPolicy,
        "woocommerce-process-checkout-nonce": php_object.checkoutNonce,
        selected_extras: this.extras.selected.map((extra) => extra.id),
      };
      if (this.checkout.type === "private") {
        data.billing_id_code = this.checkout.fields.identifierCode;
        data.billing_first_name = this.checkout.fields.firstName;
        data.billing_last_name = this.checkout.fields.lastName;
      } else {
        data.billing_company = this.checkout.fields.companyName;
        data.billing_company_registry = this.checkout.fields.registryCode;
        data.billing_first_name = this.checkout.fields.representativeFirstName;
        data.billing_last_name = this.checkout.fields.representativeLastName;
      }

      this.axios
        .post(`${php_object.ajax_url}?action=jj_checkout`, Qs.stringify(data))
        .then(function (response) {
          if (response.data.result === "success") {
            window.location = response.data.redirect;
          } else {
            // self.checkoutErrors
          }
          self.loader.isLoading = false;
        })
        .catch(function (error) {
          console.log(error);
          self.loader.isLoading = false;
        });
    },
    updateAvailableBoxes(force) {
      const self = this;
      if (
        (this.datepicker.checkIn &&
          this.checkout.location &&
          !this.locationHasBoxes()) ||
        force
      ) {
        self.loader.isLoading = true;
        this.axios
          .get(`${php_object.ajax_url}?action=get_available_boxes`, {
            params: {
              checkIn: this.datepicker.checkIn,
              checkOut: this.datepicker.checkOut,
              location: this.checkout.location.value,
            },
          })
          .then(function (response) {
            if (response.data.success) {
              self.addLocationBoxes(response.data.data);
            }
            self.checkout.box = null;
            self.loader.isLoading = false;
          })
          .catch(function (error) {
            console.log(error);
            self.location.boxes = [];
            self.loader.isLoading = false;
          });
      } else {
        // this.location.boxes = [];
        this.checkout.box = null;
      }
    },
  },
});
