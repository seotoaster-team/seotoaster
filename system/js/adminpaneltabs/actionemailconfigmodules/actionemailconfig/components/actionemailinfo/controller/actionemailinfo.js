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
            leadId: 0,
            activeTab:'',
            activeSubTab:'',
            customSubTabsList:[],
            customTabsObject:{},
            activeCustomTabId:'',
            defaultActiveTabName:'timeline',
            defaultActiveSubTabName:'notes',
            additionalNavigationData:[],
            searchable:true,
            allowedTabNames:['timeline', 'profile', 'opportunities', 'emailSmsSequence', 'additionalInfo'],
            allowedSubTabNames:['notes', 'task', 'meeting', 'email', 'sms', 'call', 'opportunity', 'documents'],
            componentKey:0,
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
        }),
    },
    watch: {
        changeMainTabRemote(newData, originalData) {
            if (typeof newData.tabName !== 'undefined' && newData.tabName !== '') {
                if (typeof newData.visitPageNumber !== 'undefined') {
                    this.changeVisitsTab(newData.tabName, newData.visitPageNumber);
                } else {
                    this.changeTab(newData.tabName);
                }
            }
        },
        changeSubTabRemote(newData, originalData) {
            if (typeof newData.tabName !== 'undefined' && newData.tabName !== '') {
                this.changeSubTab(newData.tabName, true);
            }
        },
        showSentimentChart(newVal) {
            if (newVal && !this.sentimentChart) {
                this.$nextTick(() => {
                    this.createGauge();
                });
            }
        }
    },
    methods: {
        backToGrid() {
            this.$store.commit('setNotesSubTabData', []);
            this.$router.push({ name: 'grid'});
        },
        changeTab(activeTabName){
            this.activeTab = activeTabName;
            this.$router.push({ name: 'lead', params: {'id': this.leadId },  query: {tabName: activeTabName, subTabName:this.activeSubTab}});
        },
        changeSubTab(activeSubTabName, forceOpen){
            let forceOpenFlag = forceOpen || false;

            if (this.activeSubTab === activeSubTabName && forceOpenFlag !== true) {
                this.activeSubTab = '';
            } else {
                if (forceOpenFlag === true) {
                    if (activeSubTabName === 'opportunity') {
                        this.oppportunitySubTabKey += 1;
                    }

                    this.activeSubTab = activeSubTabName;
                } else {
                    this.activeSubTab = activeSubTabName;
                }
            }

            this.activeCustomTabId = '';

            if (this.activeSubTab === '') {
                this.$router.push({ name: 'lead', params: {'id': this.leadId },  query: {tabName: this.activeTab}});
            } else {
                this.$router.push({ name: 'lead', params: {'id': this.leadId },  query: {tabName: this.activeTab, subTabName:activeSubTabName}});
            }
        },
        changeCustomSubTab(activeSubTabName, customTabId){

            if (this.activeSubTab === activeSubTabName) {
                this.activeSubTab = '';
                this.activeCustomTabId = '';
            } else {
                this.activeSubTab = activeSubTabName;
                this.activeCustomTabId = customTabId;
            }

            if (this.activeSubTab === '') {
                this.$router.push({ name: 'lead', params: {'id': this.leadId },  query: {tabName: this.activeTab}});
            } else {
                this.$router.push({ name: 'lead', params: {'id': this.leadId },  query: {tabName: this.activeTab, subTabName:activeSubTabName}});
            }
        },
        setAllTabs(){
            this.$router.push({ name: 'lead', params: {'id': this.leadId },  query: {tabName: this.activeTab, subTabName:this.activeSubTab}});
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
        updateKey() {
            this.componentKey += 1;
        },

    },
    async created(){
        if (typeof this.localeMapping[this.locale] !== 'undefined') {
            this.$i18n.locale = this.localeMapping[this.locale];
        }

        this.leadId = this.$route.params.id;

        const result = await this.$store.dispatch('getLeadDataInfo', {
            'router': this.$router,
            'id': this.leadId
        });

        if (typeof result.id === 'undefined') {
            this.$router.push({ name: 'grid'});
        } else {
            this.loadedScreen = true;

            let self = this;
            let leadTagIds = [];

            console.log(result);

            if (typeof result.customConfigsTabs !== 'undefined') {
                this.customSubTabsList = result.customConfigsTabs
            }

            if (Object.keys(this.customSubTabsList).length > 0) {
                this.customSubTabsList.forEach(function(customTab){
                    self.allowedSubTabNames.push(customTab.tab_name);
                    self.customTabsObject[customTab.tab_name] = customTab.id;
                });
            }

            if (typeof this.$route.query.tabName !== 'undefined' && this.allowedTabNames.includes(this.$route.query.tabName)) {
                this.activeTab = this.$route.query.tabName;
            } else {
                this.activeTab = this.defaultActiveTabName;
            }

            if (typeof this.$route.query.subTabName !== 'undefined' && this.allowedSubTabNames.includes(this.$route.query.subTabName)) {
                this.activeSubTab = this.$route.query.subTabName;
                if (typeof this.customTabsObject[this.activeSubTab] !== 'undefined') {
                    this.activeCustomTabId = this.customTabsObject[this.activeSubTab];
                }
            } else {
                this.activeSubTab = this.defaultActiveSubTabName;
            }
        }

    },
    async updated() {
        this.$nextTick(function () {
            this.processPhoneMobileCountryCodesSelectors();
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
                    if (typeof additionalParams.tabName !== 'undefined' && vm.allowedTabNames.includes(additionalParams.tabName)) {
                        vm.activeTab = additionalParams.tabName;
                        updateNavigation = true;
                    }
                }

                const regexSubTab = /(\bsubTabName=[\w]*)/i;
                if (pathParams.indexOf('?') > -1 && pathParams.match(regexSubTab) && pathParams.match(regexSubTab).length >= 1) {
                    additionalParams = vm.getParams(pathParams.match(regexSubTab)[0].trim());
                    if (typeof additionalParams.subTabName !== 'undefined' && vm.allowedSubTabNames.includes(additionalParams.subTabName)) {
                        vm.activeSubTab = additionalParams.subTabName;
                        if (typeof vm.customTabsObject[vm.activeSubTab] !== 'undefined') {
                            vm.activeCustomTabId = vm.customTabsObject[vm.activeSubTab];
                        }
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
