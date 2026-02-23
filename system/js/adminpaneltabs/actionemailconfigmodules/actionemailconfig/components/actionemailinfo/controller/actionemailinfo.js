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
            activeTab:''
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
            detailedScreenConfigData: 'getDetailedScreenConfigData'
        }),
    },
    watch: {
        changeSubTabRemote(newData, originalData) {
            if (typeof newData.tabName !== 'undefined' && newData.tabName !== '') {
                this.changeSubTab(newData.tabName, true);
            }
        },
    },
    methods: {
        backToGrid() {
            this.$router.push({ name: 'grid'});
        },
        changeTab(activeTabName){
            this.activeTab = activeTabName;
            this.$router.push({ name: 'actionemail', params: {'id': this.configId },  query: {tabName: activeTabName}});
        },
        closePopup(event)
        {
            if (window.parent && window.parent.$) {
                window.parent.$('.__tpopup').dialog('close');
            }
        },
        changeEventArea(event)
        {
          if (parseInt(this.configId) === 0) {
              this.$router.push({ name: 'grid'});
          } else {
              this.$router.push({ name: 'actionemail', params: {'id': this.configId }});
          }
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

        this.configId = this.$route.params.id;

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

            if (typeof this.$route.query.tabName !== 'undefined') {
                this.activeTab = this.$route.query.tabName;
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
