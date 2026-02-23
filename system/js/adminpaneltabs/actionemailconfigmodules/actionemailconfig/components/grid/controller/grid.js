import {mapGetters} from 'vuex';
import lodash from 'lodash';
import localeMapping from '../../../localizationLanguages';
import pagination from '../../pagination';
import moment from 'moment';
import { isProxy, toRaw } from 'vue';
export default {
    data () {
        return {
            loadedScreen: false,
            websiteUrl: $('#website_url').val(),
            localeMapping: localeMapping,
            locale: $('#config-system-language').val(),
            searchData:[],
            componentKey: 0,
            eventAreaId:0

        }
    },
    components: {
    },
    computed: {
        ...mapGetters({
            formatDate: 'formatDate',
            formatOnlyDate: 'formatOnlyDate',
            formatTimeOnly: 'formatTimeOnly',
            configDataInfo:'getConfigDataInfo',
            additionalInfo:'getAdditionalInfo',
            truncateText: 'truncateText',
            sortByColumn: 'sortByColumn',
            filterData:'getFilterData',
            unescapeValue:'unescapeValue',
        }),
    },
    watch: {
    },
    methods: {
        async goToDetailsScreen(id, tabName)
        {
            let openTabName = tabName || '';

            if (openTabName !== '') {
                this.$router.push({ name: 'actionemail', params: {'id': id}, query:{'tabName': openTabName}});
            } else {
                this.$router.push({name: 'actionemail', params: {'id': id}});
            }
        },
        async applyFilter()
        {
             this.$store.commit('setChangeFilter', {
                 'searchData':this.searchData,
             });

            this.$store.commit('setCheckedItems', {});
        },
        async resetFilter()
        {
            this.searchData = [];
            this.applyFilter();
        },
        formatAction(key) {
            return key.toLowerCase().replace(/ /g, '-')
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
                this.$router.push({ name: 'actionemail', params: {'id': this.eventAreaId }});
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
        }
    },
    async created(){
        if (typeof this.localeMapping[this.locale] !== 'undefined') {
            this.$i18n.locale = this.localeMapping[this.locale];
        }

        const result = await this.$store.dispatch('getGeneralScreenData', {'router':this.$router});
        if(result.status === 'error') {
            showMessage('Please re-login', true, 3000);
        } else {
            this.loadedScreen = true;
            this.mainScreenLoaded = true;
        }

    },
    async updated() {
        this.$nextTick(function () {
            if (typeof checkboxRadioStyle !== 'undefined' && typeof checkboxRadioStyle() === "function") {
                checkboxRadioStyle();
            }
        })
    }
}
