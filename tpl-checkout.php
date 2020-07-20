<section id="vue--checkout">
	<div class="loader-container" v-view="loaderScrollHandler" v-bind:class="{ active: loader.isLoading }">
		<div class="lds-dual-ring" v-bind:style="{ position: loader.styles.position, top: loader.styles.top, bottom: loader.styles.bottom }"></div>
	</div>
	<form action="<?php echo admin_url('admin-post.php') ?>" method="post" v-on:submit.prevent="submitPurchase">
		<section class="box">
			<header class="box__title">
				<h2><?php _e('Vali asukoht ja periood', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="box__container">
				<div class="box__location">
					<v-select placeholder="<?php _e('Lao asukoht', THEME_TEXT_DOMAIN); ?>" v-model="checkout.location" :options="location.locations"></v-select>
				</div>
				<div class="box__date">
					<v-date-picker :start-date="new Date()" :end-date="datepickerEndDate" :enable-checkout="true" :hovering-tooltip="true" :i18n="datepicker.locale" v-on:check-in-changed="updateCheckInDate($event)" v-on:check-out-changed="datepicker.checkOut = $event" format="DD/MM/YYYY" :min-nights="datepicker.minNights" :first-day-of-week="1" :single-day-selection="datepicker.isInfinite" />
					<p class="" v-if="errorMessage">{{ errorMessage }}</p>
				</div>
				<div class="box__map">
					<button type="button" class="green-button" :disabled="selectedLocationHref == ''" v-on:click.prevent="location.displayIframe = true"><?php _e('Vaata kaardil', THEME_TEXT_DOMAIN); ?></button>
					<div class="box__map--popup-container" style="z-index: -1;" v-if="location.displayIframe">
						<button class="green-button" v-on:click.prevent="location.displayIframe = false">X</button>
						<div class="box__map--popup-iframe" v-html="selectedLocationHref"></div>
					</div>
				</div>

				<div class="box__type">
					<div class="box__type--single">
						<p><?=__('Bokse on võimalik ette broneerida kuni 14 päeva.', THEME_TEXT_DOMAIN);?></p><br>
						<p><?=__('Boksi broneeringu minimaalne kestvus on 31 päeva.', THEME_TEXT_DOMAIN);?></p><br>
					</div>
					<div class="box__type--single">
						<input type="checkbox" name="box__type" v-on:change="changeDatePickerMode($event)" />
						<label for="box__type"><?php _e('Tähtajatu', THEME_TEXT_DOMAIN); ?></label>
					</div>
				</div>

				<div class="box__selected" v-if="location.boxes.length">
					<p>
						<?php _e('Saadaolevaid ladusid', THEME_TEXT_DOMAIN); ?>: <strong>{{location.boxes.filter(b=>b.can_book).length}}</strong>
					</p>
					<v-select placeholder="<?php _e('Vali ladu', THEME_TEXT_DOMAIN); ?>" v-model="checkout.box" :options="location.boxes" label="name_price" :selectable="option => option.can_book">
						<template v-slot:option="option"  >
							<div v-if="option.can_book">
								{{ option.name }} ( <span v-html="option.price_html"></span> )
							</div>
							<div class="striketrough" v-if="!option.can_book">
								{{ option.name }} ( <span v-html="option.price_html"></span> ) <?php _e('Broneeritud', THEME_TEXT_DOMAIN);?>
							</div>
						</template>
					</v-select>
				</div>
				<div class="box__selected" v-if="!location.boxes.length">
					<p>
						<?php _e('Lao valimiseks palun vali esmalt asukoht ja soovitud kuupäev(ad)', THEME_TEXT_DOMAIN); ?>
					</p>
				</div>
			</div>
		</section>
		<section class="checkout" v-if="checkout.box">
			<header class="checkout__title">
				<h2><?php _e('Sisesta kontaktandmed', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="checkout__container">
				<div class="checkout__header">
					<button class="green-button checkout__header--button private" v-bind:class="{ active: checkout.type === 'private' }" v-on:click.prevent="checkout.type='private'">
						<?php _e('Eraklient', THEME_TEXT_DOMAIN); ?>
					</button>
					<button class="green-button checkout__header--button commercial" v-bind:class="{ active: checkout.type === 'commercial' }" v-on:click.prevent="checkout.type='commercial'">
						<?php _e('Äriklient', THEME_TEXT_DOMAIN); ?>
					</button>
				</div>
				<div class="checkout__fields">
					<!-- Private customer specific fields -->
					<div class="checkout__fields--private" v-if="checkout.type==='private'">
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Eesnimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.firstName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Perekonnanimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.lastName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Isikukood', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.identifierCode">
						</div>
					</div>

					<div class="checkout__fields--commercial" v-if="checkout.type==='commercial'">

						<!-- Commercial customer specific fields -->
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Esindaja nimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.representativeFirstName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Esindaja perekonnanimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.representativeLastName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Ettevõte', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.companyName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Registrikood', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.registryCode">
						</div>
					</div>

					<div class="checkout__fields--general">

						<!-- General fields -->
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('E-posti aadress', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.email">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Aadress', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.address">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Postiindeks', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.postcode">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Vald, linn, asula või küla', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.jurisdiction">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php _e('Riik', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.country">
						</div>
					</div>
				</div>
				<div class="checkout__phone">
					<div class="checkout__phone--input">
						<input type="tel" placeholder="<?php _e('Telefoni nr', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.mobile">
						<button class="green-button" :disabled="!checkout.fields.mobile || mobile.validated" v-on:click.prevent="sendValidationCode"><?php _e('Saada valideerimis kood', THEME_TEXT_DOMAIN); ?></button>
					</div>
					<div class="checkout__phone--input">
						<input type="text" placeholder="<?php _e('Valideerimiskood', THEME_TEXT_DOMAIN); ?>" v-model="mobile.code">
						<button class="green-button" v-if="!mobile.validated" :disabled="!mobile.code || mobile.validated" v-on:click.prevent="validatePhone"><?php _e('Valideeri', THEME_TEXT_DOMAIN); ?></button>
						<button class="green-button" v-if="mobile.validated" v-on:click.prevent=""><?php _e('Valideeritud', THEME_TEXT_DOMAIN); ?></button>
					</div>
					<div class="checkout__phone--error error">
						<p>{{mobile.error}}</p>
					</div>
					<div class="checkout__phone--text">
						<p><?php _e('"Saada valideerimis kood" nupule vajutades saadetakse Teile SMS 8-kohalise numbriga, mille peate sisestama "Valideerimiskood" välja.', THEME_TEXT_DOMAIN); ?></p>
						<p><?php _e('Makset ei ole võimalik sooritada ilma telefoni numbri valideerimiseta.', THEME_TEXT_DOMAIN); ?></p>
					</div>
				</div>

			</div>
		</section>
		<section class="extras" v-if="true || checkout.box && mobile.validated">
			<header class="extras__title">
				<h2><?php _e('Vali lisateenused', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="extras__container">
				<div class="extras__field" v-for="extra in extras.available" v-if="true || checkout.location && checkout.location.label">
					<input type="checkbox" :name="'extra['+extra.id+']'" v-on:change="changeSelectedExtra($event, extra)" />
					<label :for="'extra['+extra.id+']'">{{extra.label}} (&nbsp;<span :inner-html.prop="extra.price_html"></span>&nbsp;)</label>
					<button v-if="extra.tippy" type="button" :content="extra.tippy" v-tippy="{ theme: 'light-border', placement : 'left',  arrow: true }">?</button>
				</div>
				<div class="extras__field" v-if="!checkout.box">
					<p><?php _e('Esmalt vali ladu', THEME_TEXT_DOMAIN); ?></p>
				</div>
			</div>
		</section>
		<section class="confirmation" v-if="checkout.box && mobile.validated">
			<header class="confirmation__title">
				<h2><?php _e('Tellimuse kinnitus', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="confirmation__grid">
				<div class="confirmation__grid--single">
					<p>
						<strong><?= __('Lao asukoht', THEME_TEXT_DOMAIN); ?>:</strong> {{selectedLocationLabel}}
					</p>
					<p>
						<strong><?= __('Periood', THEME_TEXT_DOMAIN); ?>:</strong> {{datepicker.checkIn | formatDate}} - {{datepicker.checkOut | formatDate}}
					</p>
				</div>
				<div class="confirmation__grid--single">
					<p><strong><?= __('Valitud lisateenused', THEME_TEXT_DOMAIN); ?>:</strong></p>
					<ul class="confirmation__extras--list">
						<li v-for="extra in extras.selected">{{extra.label}} (&nbsp;<span :inner-html.prop="extra.price_html"></span>&nbsp;)</li>
					</ul>
				</div>

			</div>
			<div class="confirmation__row">
				<p><strong><?= __('Summa', THEME_TEXT_DOMAIN); ?>:</strong> <span :inner-html.prop="totalSum | formatSum"></span> <span v-if="totalSum !== 0">(<?php _e('sisaldab 20% käibemksu', THEME_TEXT_DOMAIN); ?>)</span></p>
			</div>
			<div class="confirmation__row">
				<p><strong><?= __('Arve kinnitus ning uksekood saadetakse teile e-maili peale pärast makse sooritamist.', THEME_TEXT_DOMAIN); ?></strong></p>
				<div class="confirmation__checkbox">
					<input type="checkbox" name="checkout[privacy]" v-model="checkout.privacyPolicy" />
					<label><?php
									echo sprintf(
										__('Nõustun %s ja %s.', THEME_TEXT_DOMAIN),
										'<a href="' . get_privacy_policy_url() . '">' . __('kasutajatingimuste', THEME_TEXT_DOMAIN) . '</a>',
										'<a href="' . get_privacy_policy_url() . '">' . __('privaatsuspoliitikaga', THEME_TEXT_DOMAIN) . '</a>'
									);
									?>
					</label>
				</div>
			</div>

			<div class="confirmation__errors" v-if="checkoutErrors.length > 0">
				<div class="confirmation__error error" v-for="error in checkoutErrors">{{error}}</div>
			</div>

			<div class="confirmation woocommerce-confirmation">
				<div class="confirmation__payment confirmation__payment--single">
					<div class="confirmation__payment--single-makecommerce">

					</div>
					<div class="confirmation__payment--single-submit">
						<input type="submit" class="green-button" :disabled="checkoutErrors.length > 0" value="<?php _e('Maksa', THEME_TEXT_DOMAIN); ?>">
					</div>
				</div>
			</div>
		</section>

	</form>

</section>