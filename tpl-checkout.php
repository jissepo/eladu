<section id="vue--checkout" sticky-container>
	<div class="loader-container" v-view="loaderScrollHandler" v-bind:class="{ active: loader.isLoading }">
		<div class="lds-dual-ring" v-bind:style="{ position: loader.styles.position, top: loader.styles.top, bottom: loader.styles.bottom }"></div>
	</div>
	<div class="price__floater" v-sticky sticky-offset="{ top: 110, bottom: 0 }">
		<div class="price__floater_box">
			<div class="step" :class="{active:currentStep >= 1}">
				<span>1</span>
				<span>
					<?php echo esc_attr_e('Vali ladu', THEME_TEXT_DOMAIN); ?>
				</span>
			</div>
			<div class="step" :class="{active:currentStep >= 2}">
				<span>2</span>
				<span>
					<?php echo esc_attr_e('Kontaktandmed', THEME_TEXT_DOMAIN); ?>
				</span>
			</div>
			<div class="step" :class="{active:currentStep >= 3}">
				<span>3</span>
				<span>
					<?php echo esc_attr_e('Maksmine', THEME_TEXT_DOMAIN); ?>
				</span>
			</div>
			<div class="active price">
				<strong><?php echo __('Summa', THEME_TEXT_DOMAIN); ?>:</strong>&nbsp;
				<span :inner-html.prop="totalSum | formatSum"></span>&nbsp;
				<!-- <span v-if="totalSum !== 0">(<?php _e('sis. KM', THEME_TEXT_DOMAIN); ?>)</span> -->
			</div>
		</div>
		<div class="price__floater_box--bottom" v-if="alerts.length">
			<ul>
				<li class="alert alert-danger" v-for="(alert, index) in alerts" :key="`alert--${index}`">{{alert}}</li>
			</ul>
		</div>
	</div>
	<form action="<?php echo admin_url('admin-post.php'); ?>" method="post" v-on:submit.prevent="submitPurchase">


		<section class="box">
			<h1 class="section-title" :class="{active:currentStep >= 1}">
				<div class="section-number">1</div>
			</h1>
			<header class="box__title">
				<h2><?php esc_html_e('Vali asukoht ja periood', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="box__container">
				<div class="box__location">
					<v-select placeholder="<?php esc_html_e('Lao asukoht', THEME_TEXT_DOMAIN); ?>" v-model="checkout.location" :options="location.locations"></v-select>
				</div>
				<div class="box__date">
					<v-date-picker :start-date="datepickerStartDate" :end-date="datepickerEndDate" :enable-checkout="true" :hovering-tooltip="true" :i18n="datepicker.locale" :min-nights="datepicker.minNights" :first-day-of-week="1" :single-day-selection="datepicker.isInfinite" @disabled-day-clicked='handleDisabledDayClick($event)' v-on:check-in-changed="updateCheckInDate($event)" v-on:check-out-changed="datepicker.checkOut = $event; highestStep = (highestStep === 0 ? 1 : highestStep)" format="DD/MM/YYYY" ref="datePicker" />
					<p class="" v-if="errorMessage">{{ errorMessage }}</p>
				</div>
				<div class="box__map">
					<button type="button" class="green-button" :disabled="selectedLocationHref == ''" v-on:click.prevent="location.displayIframe = true"><?php esc_html_e('Vaata kaardil', THEME_TEXT_DOMAIN); ?></button>
					<div class="box__map--popup-container" style="z-index: -1; display: none;" v-show="location.displayIframe">
						<button class="green-button" v-on:click.prevent="location.displayIframe = false">X</button>
						<div class="box__map--popup-iframe" v-html="selectedLocationHref"></div>
					</div>
					<div class="box__map--popup-container" style="z-index: -1; display: none;" v-show="popupImageLink !== ''">
						<button class="green-button" v-on:click.prevent="popupImageLink = ''">X</button>
						<div class="box__map--popup-iframe">
							<img :src="popupImageLink" class="extra__image">
						</div>
					</div>
					<div class="box__map--popup-container" style="z-index: -1; display: none;" v-show="popupDisabledDate">
						<button class="green-button" v-on:click.prevent="popupDisabledDate = false">X</button>
						<div class="box__map--popup-iframe date_text">
							<?php the_field('disabled_date_text'); ?>
						</div>
					</div>
				</div>

				<div class="box__type">
					<div class="box__type--single">
						<p><strong><?php echo sprintf(__('Bokse on võimalik ette broneerida kuni 14 päeva. Kui soovite pikemalt ette broneerida, siis %1$s võtke meiega ühendust. %2$s', THEME_TEXT_DOMAIN), '<a href="' . site_url() . '#kontakt">', '</a>'); ?></strong></p><br>
						<p><?php echo __('Boksi broneeringu minimaalne kestvus on 31 päeva.', THEME_TEXT_DOMAIN); ?></p><br>
					</div>
					<div class="box__type--single">
						<input type="checkbox" name="box__type" v-on:change="changeDatePickerMode($event)" />
						<label for="box__type"><?php esc_html_e('Tähtajatu', THEME_TEXT_DOMAIN); ?></label>
					</div>
				</div>
				<div class="box__layout">
					<iframe style="width:100%;height:600px;" src="https://docs.google.com/spreadsheets/d/e/2PACX-1vS1x_uwFbjlccao-0hbZGnDs955JB_Gp25T6n6MzKvG7Pm3zEeg-znzse4Nwl99J9-JlfP83fbhDBAQ/pubhtml?gid=244141283&amp;single=true&amp;widget=true&amp;headers=false"></iframe>
				</div>
				<div class="box__selected" v-if="locationHasBoxes()">
					<h2><?php esc_html_e('Vali laoboks', THEME_TEXT_DOMAIN); ?></h2>
					<p>
						<?php esc_html_e('Saadaolevaid ladusid', THEME_TEXT_DOMAIN); ?>: <strong>{{this.location.locations[this.getSelectedLocationIndex()].boxes.filter(b=>b.can_book).length}}</strong>
					</p>
					<v-select placeholder="<?php esc_html_e('Vali ladu', THEME_TEXT_DOMAIN); ?>" v-model="checkout.box" :options="this.location.locations[this.getSelectedLocationIndex()].boxes" label="name_price" :selectable="option => option.can_book">
						<template v-slot:option="option">
							<div v-if="option.can_book">
								{{ option.name }}
							</div>
							<div class="striketrough" v-if="!option.can_book">
								{{ option.name }}  <?php esc_html_e('Broneeritud', THEME_TEXT_DOMAIN); ?>
							</div>
						</template>
					</v-select>
				</div>
				<div class="box__selected" v-if="!locationHasBoxes()">
					<h2><?php esc_html_e('Vali laoboks', THEME_TEXT_DOMAIN); ?></h2>
					<p>
						<?php esc_html_e('Lao valimiseks palun vali esmalt asukoht ja soovitud kuupäev(ad)', THEME_TEXT_DOMAIN); ?>
					</p>
				</div>

				<div class="box__pictures">
				<?php if( have_rows('ladude_pildid', 'options') ): ?>
					<div class="box__pictures--row">
						<?php while( have_rows('ladude_pildid', 'options') ): the_row();
							$image = get_sub_field('lao_pilt', 'options');
							?>
							<div>
								<a href="<?php echo $image; ?>" rel="prettyPhoto"><img src="<?php echo $image; ?>" width="200" height="200" /></a>
								<p><b><?php the_sub_field('pildi_tekst', 'options'); ?></b></p>
							</div>
						<?php endwhile; ?>
					</div>
				<?php endif; ?>
				</div>

				<div class="extras">
					<header class="extras__title">
						<h2><?php esc_html_e('Vali lisateenused', THEME_TEXT_DOMAIN); ?></h2>
					</header>
					<div class="extras__container" v-if="checkout.location && checkout.box">
						<div class="extras__field" v-for="extra in checkout.location.extras">
							<div class="extras__field--container">
								<input type="checkbox" :name="'extra['+extra.id+']'" v-on:change="changeSelectedExtra($event, extra)" />
								<label :for="'extra['+extra.id+']'">{{extra.label}}(&nbsp;<span :inner-html.prop="extra.price_html"></span>&nbsp;)</label>
							</div>
							<span class="image" v-if="extra.image !== null" v-on:click="popupImageLink = extra.image"><?php _e('Vaata pilti', THEME_TEXT_DOMAIN); ?></span>
							<button class="jj-tippy" v-if="extra.tippy" type="button" :content="extra.tippy" v-tippy="{ theme: 'light-border', placement : 'left',  arrow: true }">?</button>
						</div>
					</div>
					<div class="extras__field" v-if="!checkout.box">
						<p><?php esc_html_e('Esmalt vali ladu', THEME_TEXT_DOMAIN); ?></p>
					</div>
					<div class="extras__field" v-if="checkout.box && checkout.location && checkout.location.extras.length <= 0">
						<p><?php esc_html_e('Antud laol puudvad lisateenused', THEME_TEXT_DOMAIN); ?></p>
					</div>
				</div>
			</div>
		</section>
		<section class="checkout" v-if="currentStep >= 2">
			<h1 class="section-title">
				<div class="section-number">2</div>
			</h1>
			<header class="checkout__title">
				<h2><?php esc_html_e('Sisesta kontaktandmed', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="checkout__container">
				<div class="checkout__header">
					<button class="green-button checkout__header--button private" v-bind:class="{ active: checkout.type === 'private' }" v-on:click.prevent="checkout.type='private'">
						<?php esc_html_e('Eraklient', THEME_TEXT_DOMAIN); ?>
					</button>
					<button class="green-button checkout__header--button commercial" v-bind:class="{ active: checkout.type === 'commercial' }" v-on:click.prevent="checkout.type='commercial'">
						<?php esc_html_e('Äriklient', THEME_TEXT_DOMAIN); ?>
					</button>
				</div>
				<div class="checkout__fields">
					<!-- Private customer specific fields -->
					<div class="checkout__fields--private" v-if="checkout.type==='private'">
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Eesnimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.firstName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Perekonnanimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.lastName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Isikukood', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.identifierCode">
						</div>
					</div>

					<div class="checkout__fields--commercial" v-if="checkout.type==='commercial'">

						<!-- Commercial customer specific fields -->
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Esindaja nimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.representativeFirstName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Esindaja perekonnanimi', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.representativeLastName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Ettevõte', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.companyName">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Registrikood', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.registryCode">
						</div>
					</div>

					<div class="checkout__fields--general">

						<!-- General fields -->
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('E-posti aadress', THEME_TEXT_DOMAIN); ?>" required v-model="checkout.fields.email">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Aadress', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.address">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Postiindeks', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.postcode">
						</div>
						<div class="checkout__fields--single">
							<input type="text" placeholder="<?php esc_html_e('Vald, linn, asula või küla', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.jurisdiction">
						</div>
						<div class="checkout__fields--single">
							<v-select placeholder="<?php esc_html_e('Riik', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.country" :options="countries"></v-select>
						</div>
					</div>
				</div>
				<div class="checkout__phone">
					<div class="checkout__phone--input">
						<input type="tel" placeholder="<?php esc_html_e('Telefoni nr', THEME_TEXT_DOMAIN); ?>" v-model="checkout.fields.mobile">
						<button class="green-button" :disabled="!checkout.fields.mobile || mobile.validated " v-on:click.prevent="sendValidationCode"><?php esc_html_e('Saada valideerimis kood', THEME_TEXT_DOMAIN); ?></button>
					</div>
					<div class="checkout__phone--input">
						<input type="text" placeholder="<?php esc_html_e('Valideerimiskood', THEME_TEXT_DOMAIN); ?>" v-model="mobile.code">
						<button class="green-button" v-if="!mobile.validated" :disabled="!mobile.code || mobile.validated" v-on:click.prevent="validatePhone"><?php esc_html_e('Valideeri', THEME_TEXT_DOMAIN); ?></button>
						<button class="green-button" v-if="mobile.validated" v-on:click.prevent=""><?php esc_html_e('Valideeritud', THEME_TEXT_DOMAIN); ?></button>
					</div>
					<div class="checkout__phone--error error">
						<p>{{mobile.error}}</p>
					</div>
					<div class="checkout__phone--error success">
						<p>{{mobile.success}}</p>
					</div>
					<div class="checkout__phone--text">
						<p><?php esc_html_e('"Saada valideerimis kood" nupule vajutades saadetakse Teile SMS 4-kohalise numbriga, mille peate sisestama "Valideerimiskood" välja.', THEME_TEXT_DOMAIN); ?></p>
						<p><?php esc_html_e('Makset ei ole võimalik sooritada ilma telefoni numbri valideerimiseta.', THEME_TEXT_DOMAIN); ?></p>
					</div>
				</div>

			</div>
		</section>

		<section class="confirmation" v-if="currentStep >= 3">
			<h1 class="section-title">
				<div class="section-number">3</div>
			</h1>
			<header class="confirmation__title">
				<h2><?php esc_html_e('Tellimuse kinnitus', THEME_TEXT_DOMAIN); ?></h2>
			</header>
			<div class="confirmation__grid">
				<div class="confirmation__grid--single">
					<p>
						<strong><?php echo __('Lao asukoht', THEME_TEXT_DOMAIN); ?>:</strong> {{selectedLocationLabel}}
					</p>
					<p>
						<strong><?php echo __('Boksi nimetus', THEME_TEXT_DOMAIN); ?>:</strong> {{selectedBoxName}}
					</p>
					<p>
						<strong><?php echo __('Periood', THEME_TEXT_DOMAIN); ?>:</strong> {{datepicker.checkIn | formatDate}} - {{datepicker.checkOut | formatDate}}
					</p>
				</div>
				<div class="confirmation__grid--single">
					<p><strong><?php echo __('Valitud lisateenused', THEME_TEXT_DOMAIN); ?>:</strong></p>
					<ul class="confirmation__extras--list">
						<li v-for="extra in extras.selected">{{extra.label}} (&nbsp;<span :inner-html.prop="extra.price_html"></span>&nbsp;)</li>
					</ul>
				</div>

			</div>
			<div class="confirmation__row">
				<p><strong><?php echo __('Arve kinnitus ning uksekood saadetakse teile e-maili peale pärast makse sooritamist.', THEME_TEXT_DOMAIN); ?></strong></p>
				<div class="confirmation__checkbox">
					<input type="checkbox" name="checkout[privacy]" v-model="checkout.privacyPolicy" />
					<label>
						<?php
						echo sprintf(
							__('Nõustun %1$s', THEME_TEXT_DOMAIN),
							'<a href="' . site_url() . '/andmekaitseleping" target="_blank">' . __('andmekaitsetingimustega', THEME_TEXT_DOMAIN) . '</a>',
						);
						?>
					</label>
				</div>
				<div class="confirmation__checkbox">
					<input type="checkbox" name="checkout[uurileping]" v-model="checkout.uurileping" />
					<label>
						<?php
						echo sprintf(
							__('Nõustun %1$s.', THEME_TEXT_DOMAIN),
							'<a href="' . site_url() . '/uurileping" target="_blank">' . __('üürilepingu tingimustega', THEME_TEXT_DOMAIN) . '</a>',
						);
						?>
					</label>
				</div>
				<div class="confirmation__checkbox">
					<input type="checkbox" name="checkout[kasutustingimused]" v-model="checkout.kasutustingimused" />
					<label>
						<?php
						echo sprintf(
							__('Nõustun %1$s.', THEME_TEXT_DOMAIN),
							'<a href="' . site_url() . '/kasutustingimused" target="_blank">' . __('kasutustingimustega', THEME_TEXT_DOMAIN) . '</a>',
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
						<input type="submit" class="green-button" :disabled="checkoutErrors.length > 0" value="<?php esc_html_e('Maksa', THEME_TEXT_DOMAIN); ?>">
					</div>
				</div>
			</div>
		</section>
	</form>
</section>
