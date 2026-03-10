import {mapGetters} from 'vuex';
import lodash from 'lodash';
import localeMapping from '../../../localizationLanguages';
import moment from 'moment';
import {toRaw} from "vue";

export default {
    data () {
        return {
            loadedScreen: false,
            websiteUrl: $('#website_url').val(),
            localeMapping: localeMapping,
            locale: $('#config-language').val(),
            configId: 0,
            componentKey:0,
            activeTab:null,
            triggers: {},
            actions: [],
            localId: Date.now() + Math.random(),
        }
    },
    components: {
    },
    computed: {
        ...mapGetters({
            formatDate: 'formatDate',
            formatOnlyDate: 'formatOnlyDate',
            formatTimeOnly: 'formatTimeOnly',
            truncateText: 'truncateText',
            additionalInfo:'getAdditionalInfoDetailedScreen',
            sortByColumn: 'sortByColumn',
            changeFilter: 'getChangeFilter',
            unescapeValue:'unescapeValue',
            toCurrency:'toCurrency',
            detailedScreenConfigData: 'getDetailedScreenConfigData',
            eventId: 'getEventAreaId',
        }),
        processedMailTemplates() {
            if (!this.additionalInfo.mailTemplates) return [];
            return Object.keys(this.additionalInfo.mailTemplates).map(key => ({
                value: key,
                label: this.additionalInfo.mailTemplates[key]
            }));
        },
        processedServices() {
            if (!this.additionalInfo.services) return [];
            return Object.keys(this.additionalInfo.services).map(key => ({
                value: key,
                label: this.additionalInfo.services[key]
            }));
        },
        tabNames() {
            const triggers = this.additionalInfo?.triggers?.[this.configId]?.trigger;
            if (triggers && typeof triggers === 'object') {
                return Object.keys(triggers);
            }
            return [];
        }
    },
    watch: {
        changeSubTabRemote(newData, originalData) {
            if (typeof newData.tabName !== 'undefined' && newData.tabName !== '') {
                this.changeSubTab(newData.tabName, true);
            }
        },
        activeTab() {
            this.scrollToActiveTab();
        },
        tabNames(newVal) {
            if (newVal.length && this.activeTab) {
                this.scrollToActiveTab();
            }
        }
    },
    methods: {
        backToGrid() {
            this.$router.push({ name: 'grid'});
        },
        changeTab(activeTabName){
            this.activeTab = activeTabName;
            this.$router.push({ name: 'actionemail', params: {'id': this.configId },  query: {tabName: activeTabName}});
        },
        getParams(pathParams) {
            let result = {},
                tmpData = [];

            pathParams
                .split("&")
                .forEach(function (item) {
                    tmpData = item.split("=");
                    if (tmpData[0] !== '') {
                        result[decodeURIComponent(tmpData[0])] = decodeURIComponent(tmpData[1]);
                    }
                });
            return result;
        },
        updateKey()
        {
            this.componentKey += 1;
        },
        remove(localId) {
            const index = this.actions.findIndex(a => a.localId === localId);
            if (index === -1) return;

            const action = this.actions[index];

            if (action?.id) {
                action.delete = "true";
            } else {
                this.actions.splice(index, 1);
            }
        },
        scrollTabs(direction) {
            const container = this.$refs.tabsScroll;
            const tabs = this.tabNames;
            const currentIndex = tabs.indexOf(this.activeTab);

            let newIndex = currentIndex;

            if (direction === 'left' && currentIndex > 0) {
                newIndex--;
            } else if (direction === 'right' && currentIndex < tabs.length - 1) {
                newIndex++;
            }

            const newTab = tabs[newIndex];
            this.changeTab(newTab);

            this.$nextTick(() => {
                const tabEl = container.querySelectorAll('li')[newIndex];

                if (tabEl) {
                    const offsetLeft = tabEl.offsetLeft;
                    const width = tabEl.offsetWidth;
                    const containerWidth = container.offsetWidth;

                    container.scrollTo({
                        left: offsetLeft - containerWidth / 2 + width / 2,
                        behavior: 'smooth'
                    });
                }
            });
        },
        scrollToActiveTab() {
            this.$nextTick(() => {
                requestAnimationFrame(() => {
                    const container = this.$refs.tabsScroll;
                    if (!container) return;

                    const tabs = this.tabNames;
                    const index = tabs.indexOf(this.activeTab);
                    if (index === -1) return;

                    const tabEl = container.querySelectorAll('li')[index];
                    if (!tabEl) return;

                    const offsetLeft = tabEl.offsetLeft;
                    const width = tabEl.offsetWidth;
                    const containerWidth = container.offsetWidth;
                    const maxScrollLeft = container.scrollWidth - containerWidth;

                    let scrollLeft = offsetLeft - containerWidth / 2 + width / 2;

                    // prevent scrolling beyond max
                    if (scrollLeft > maxScrollLeft) scrollLeft = maxScrollLeft;
                    if (scrollLeft < 0) scrollLeft = 0;

                    container.scrollTo({
                        left: scrollLeft,
                        behavior: 'smooth'
                    });
                });
            });
        },
        filteredActions(triggerName) {
            return this.actions.filter(a => a.trigger === triggerName);
        },
        showServiceSelector(name, data)
        {
            return name === 'store_neworder'
                || name === 'store_trackingnumber'
                || typeof data.withsms !== 'undefined';
        },
        async saveAction() {
            const actionsPayload = {};

            this.actions
                .filter(action => action.trigger in this.additionalInfo.triggers[this.configId].trigger)
                .forEach(action => {

                    // Ensure template exists for SMS BEFORE building payload
                    if (action.service === 'sms' && !action.template) {
                        action.template = this.processedMailTemplates[0]?.value || '';
                    }

                    const key = action.id || `new_${action.localId}`;

                    const payload = {
                        trigger: action.trigger,
                        service: action.service,
                        recipient: action.recipient,
                        message: action.message || '',
                        from: action.from || '',
                        subject: action.subject || '',
                        preheader: action.preheader || '',
                        smsText: action.smsText || (action.service === 'sms' ? (action.message || '') : ''),
                        delete: action.delete === true || action.delete === "true" ? "true" : undefined,
                        id: action.id || undefined
                    };

                    // add template only if not empty
                    if (action.template && action.template !== '') {
                        payload.template = action.template;
                    }

                    actionsPayload[key] = payload;
                });

            const result = await this.$store.dispatch('saveTriggerActions', {
                'router': this.$router,
                'id': this.configId,
                'actionsPayload': actionsPayload
            });

            if (parseInt(result.error) === 0) {
                this.additionalInfo.triggerActions = result.responseText.actions;

                this.actions = this.additionalInfo.triggerActions.map(a => {
                    // Determine default template
                    let template = a.template;

                    // If service is SMS and template is empty, use first available template
                    if(a.service === 'sms' && (!template || template === '')) {
                        template = this.processedMailTemplates[0]?.value || '';
                    }

                    return {
                        id: a.id,
                        trigger: a.trigger,
                        service: a.service,
                        recipient: a.recipient,
                        template: template,
                        message: a.message,
                        from: a.from,
                        subject: a.subject,
                        preheader: a.preheader,
                        localId: Date.now() + Math.random()
                    };
                });

                showMessage(result.responseText.message, false, 3000);
            } else {
                showMessage(result.responseText, true, 5000);
            }
        },
        addAction(triggerName) {
            // pick a default template (you can use first available template)
            const defaultTemplate = this.processedMailTemplates[0]?.value || '';

            var message = '';
            var from = '';
            var subject = '';
            var preheader = '';
            var recipient = 'admin';

            if (typeof this.additionalInfo.triggers[this.configId] !== 'undefined' && typeof this.additionalInfo.triggers[this.configId]['trigger'] !== 'undefined'
                && typeof this.additionalInfo.triggers[this.configId]['trigger'][triggerName] !== 'undefined') {

                from = this.additionalInfo.triggers[this.configId]['trigger'][triggerName]['from'];
                message = this.additionalInfo.triggers[this.configId]['trigger'][triggerName]['message'];
                recipient = this.additionalInfo.triggers[this.configId]['trigger'][triggerName]['sendto'];
                subject = this.additionalInfo.triggers[this.configId]['trigger'][triggerName]['subject'];
                preheader = this.additionalInfo.triggers[this.configId]['trigger'][triggerName]['preheader'];

            }

            this.actions.push({
                localId: Date.now() + Math.random(),
                trigger: triggerName,
                service: 'email',
                recipient: 'admin',
                template: defaultTemplate,
                message: message,
                from: from,
                subject: subject,
                preheader: preheader
            });
        },
        onServiceChange(action) {
            if (action.service === 'sms') {
                action._previousRecipient = action.recipient;
                action.recipient = 'customer';

                // Ensure template always exists for backend
                if (!action.template) {
                    const defaultTemplate = this.processedMailTemplates[0]?.value;

                    if (defaultTemplate) {
                        action.template = defaultTemplate;
                    }
                }
            } else {
                if (action._previousRecipient) {
                    action.recipient = action._previousRecipient;
                }

                action.smsText = '';
            }
        },
        filteredRecipients(action) {
            const recipientsArray = Object.keys(this.additionalInfo.recipients || {}).map(key => ({
                value: key,
                label: this.additionalInfo.recipients[key]
            }));

            if (action.service === 'sms') {
                return recipientsArray.filter(r =>
                    r.value === 'customer' || r.value === 'admin'
                );
            }

            return recipientsArray; // email: all recipients
        }
    },
    async created(){
        if (typeof this.localeMapping[this.locale] !== 'undefined') {
            this.$i18n.locale = this.localeMapping[this.locale];
        }

        this.configId = this.$route.params.id;

        if (this.eventId === 0) {
            this.$store.commit('setEventAreaId', {'eventAreaId':this.configId});
        }

        const result = await this.$store.dispatch('getDetailedScreenInfo', {
            'router': this.$router,
            'id': this.configId
        });

        if (typeof result.id === 'undefined') {
            this.$router.push({ name: 'grid'});
        } else {
            this.loadedScreen = true;

            let self = this;

            console.log(result);

            this.actions = this.additionalInfo.triggerActions.map(a => {
                // Determine default template
                let template = a.template;

                // If service is SMS and template is empty, use first available template
                if(a.service === 'sms' && (!template || template === '')) {
                    template = this.processedMailTemplates[0]?.value || '';
                }

                return {
                    id: a.id,
                    trigger: a.trigger,
                    service: a.service,
                    recipient: a.recipient,
                    template: template,
                    message: a.message,
                    from: a.from,
                    subject: a.subject,
                    preheader: a.preheader,
                    localId: Date.now() + Math.random()
                };
            });

            if (typeof this.$route.query.tabName !== 'undefined') {
                this.activeTab = this.$route.query.tabName;
            } else {
                if (typeof this.additionalInfo.triggers[this.configId] !== 'undefined' && typeof this.additionalInfo.triggers[this.configId]['trigger'] !== 'undefined')  {
                    const triggers = this.additionalInfo.triggers[this.configId]['trigger'];
                    const firstTrigger = Object.keys(triggers)[0];
                    if (firstTrigger) {
                        this.changeTab(firstTrigger);
                    }
                }
            }
        }

    },
    async updated() {
        this.$nextTick(function () {
            if (typeof checkboxRadioStyle !== 'undefined' && typeof checkboxRadioStyle() === "function") {
                checkboxRadioStyle();
            }
        })
    },
    mounted() {
        let vm = this;
        window.onpopstate = function(event) {
            if (event.state !== null) {
                let pathParams = decodeURI(event.state.current),
                    additionalParams = '',
                    updateNavigation = false;

                const regex = /(\btabName=[\w]*)/i;
                if (pathParams.indexOf('?') > -1 && pathParams.match(regex) && pathParams.match(regex).length >= 1) {
                    additionalParams = vm.getParams(pathParams.match(regex)[0].trim());
                    if (typeof additionalParams.tabName !== 'undefined') {
                        vm.activeTab = additionalParams.tabName;
                        updateNavigation = true;
                    }
                }

                if (updateNavigation === true) {
                    vm.setAllTabs();
                }
            }
        }
    }


}
