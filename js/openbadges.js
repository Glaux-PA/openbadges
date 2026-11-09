(function () {
	const config = window.openBadgesPlugin;
	if (!config || !window.pkp || !pkp.registry) {
		return;
	}

	const {h, ref, onMounted} = pkp.modules.vue;
	const strings = config.strings;
	const MENU_KEY = 'openbadges';

	function request(url, submissionId, body) {
		const target = url + (url.indexOf('?') === -1 ? '?' : '&') + 'submissionId=' + encodeURIComponent(submissionId);
		const options = {
			method: body ? 'POST' : 'GET',
			credentials: 'same-origin',
			headers: {'X-Requested-With': 'XMLHttpRequest'},
		};
		if (body) {
			options.body = body;
		}
		return fetch(target, options).then((response) => response.json());
	}

	const OpenBadgesPanel = {
		name: 'OpenBadgesPanel',
		props: {
			submissionId: {type: Number, required: true},
		},
		setup(props) {
			const badges = ref([]);
			const loading = ref(true);
			const saving = ref(false);
			const message = ref('');
			const failed = ref(false);
			const recentlySaved = ref(false);
			let savedTimer = null;

			function apply(json) {
				failed.value = !json.status;
				if (json.status) {
					badges.value = json.content.badges;
				} else {
					message.value = json.content || strings.error;
				}
			}

			function load() {
				loading.value = true;
				request(config.getUrl, props.submissionId)
					.then(apply)
					.catch(() => {
						failed.value = true;
						message.value = strings.error;
					})
					.finally(() => {
						loading.value = false;
					});
			}

			function save(event) {
				event.preventDefault();
				saving.value = true;
				message.value = '';
				const body = new FormData();
				body.append('csrfToken', pkp.currentUser.csrfToken);
				badges.value.forEach((badge) => {
					body.append('badges[' + badge.id + '][url]', badge.url);
					if (badge.isActive) {
						body.append('badges[' + badge.id + '][isActive]', 'on');
					}
				});
				request(config.updateUrl, props.submissionId, body)
					.then((json) => {
						apply(json);
						if (json.status) {
							recentlySaved.value = true;
							clearTimeout(savedTimer);
							savedTimer = setTimeout(() => {
								recentlySaved.value = false;
							}, 3000);
						}
					})
					.catch(() => {
						failed.value = true;
						message.value = strings.error;
					})
					.finally(() => {
						saving.value = false;
					});
			}

			onMounted(load);

			function renderBadge(badge) {
				return h('div', {class: 'openbadges-item', key: badge.id}, [
					h('div', {class: 'openbadges-image'}, [h('img', {src: badge.image, alt: badge.title})]),
					h('div', {class: 'openbadges-details'}, [
						h('h3', badge.title),
						h('p', badge.description),
						h('div', {class: 'openbadges-url'}, [
							h('label', {for: 'openbadges-url-' + badge.id}, strings.url + ':'),
							h('input', {
								type: 'text',
								id: 'openbadges-url-' + badge.id,
								pattern: '^(#|https?://\\S+)$',
								value: badge.url,
								onInput: (event) => {
									badge.url = event.target.value;
								},
							}),
						]),
						h('div', {class: 'openbadges-status'}, [
							h('input', {
								type: 'checkbox',
								id: 'openbadges-active-' + badge.id,
								checked: badge.isActive,
								onChange: (event) => {
									badge.isActive = event.target.checked;
								},
							}),
							h('label', {for: 'openbadges-active-' + badge.id}, strings.activeLabel),
						]),
					]),
				]);
			}

			return () =>
				h('form', {class: 'openbadges-panel', onSubmit: save}, [
					loading.value ? h('p', strings.loading) : badges.value.map(renderBadge),
					h('div', {class: 'pkpFormPage__footer', 'aria-live': 'polite'}, [
						h('span', {role: 'status', 'aria-live': 'polite', 'aria-atomic': 'true'}, [
							saving.value
								? h('span', {class: 'pkpFormPage__status'}, [h(pkp.registry.getComponent('PkpSpinner')), strings.saving])
								: recentlySaved.value
									? h('span', {class: 'pkpFormPage__status'}, [
											h(pkp.registry.getComponent('PkpIcon'), {icon: 'Complete', class: 'h-5 w-5 text-success', inline: true}),
											' ' + strings.saved,
										])
									: message.value
										? h('span', {class: 'pkpFormPage__status openbadges-error'}, message.value)
										: null,
						]),
						h(
							pkp.registry.getComponent('PkpButton'),
							{type: 'submit', isDisabled: loading.value || saving.value},
							() => strings.save,
						),
					]),
				]);
		},
	};

	pkp.registry.registerComponent('OpenBadgesPanel', OpenBadgesPanel);

	pkp.registry.storeExtendFn('workflow', 'getMenuItems', (items) => {
		const publication = items.find((item) => item.key === 'publication');
		if (publication) {
			publication.items = [
				...publication.items,
				{
					key: 'publication_' + MENU_KEY,
					label: strings.tabName,
					state: {
						primaryMenuItem: 'publication',
						secondaryMenuItem: MENU_KEY,
						title: strings.publication + ' — ' + strings.tabName,
					},
				},
			];
		}
		return items;
	});

	pkp.registry.storeExtendFn('workflow', 'getPrimaryItems', (items, args) => {
		if (args.selectedMenuState && args.selectedMenuState.secondaryMenuItem === MENU_KEY && args.submission) {
			return [{component: 'OpenBadgesPanel', props: {submissionId: args.submission.id}}];
		}
		return items;
	});
})();
