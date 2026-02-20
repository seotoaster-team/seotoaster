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
            locale: $('#dashboard-system-language').val(),
            searchData:[],
            componentKey: 0,

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
            defaultPresetEnabled:'getDefaultPresetEnabled',
            activeFilterPreset:'getActiveFilterPreset',
        }),
    },
    watch: {
    },
    methods: {
        async applyFilter()
        {
             this.$store.commit('setChangeFilter', {
                 'searchData':this.searchData,
             });

            this.$store.commit('setCheckedItems', {});
        },
        async resetSearchBar()
        {
            this.searchTerm = '';
            this.applyFilter();
        },
        async resetFilter()
        {
            this.searchData = [];
            this.applyFilter();
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

        const result = await this.$store.dispatch('getGeneralLeadsScreenData', {'router':this.$router});
        if(result.status === 'error') {
            showMessage('Please re-login', true, 3000);
        } else {
            let urlParamsString = window.location.search;

            if (urlParamsString !== '' && urlParamsString.indexOf('?') > -1) {
                this.urlPredefinedFilterParams = this.getParams(urlParamsString.replace('?', ''));
                this.processUrlPredefinedParams();
            } else {
                this.urlPredefinedFilterParams = [];
            }

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
