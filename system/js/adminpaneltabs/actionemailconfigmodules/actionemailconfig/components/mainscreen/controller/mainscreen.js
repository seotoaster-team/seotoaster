import {mapGetters} from 'vuex';
import lodash from 'lodash';
import localeMapping from '../../../localizationLanguages';
import pagination from '../../pagination';
import moment from 'moment';
export default {
    data () {
        return {
            loadedScreen: false,
            websiteUrl: $('#website_url').val(),
            localeMapping: localeMapping,
            locale: $('#config-system-language').val(),
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
            changeEventAreaRemoteFromStore:'getChangeEventAreaRemote',
            eventAreaIdFromStore: 'getEventAreaId',
        }),
    },
    watch: {
        changeEventAreaRemoteFromStore(newData) {
            if (newData && newData.eventAreaId !== undefined && newData.eventAreaId !== '') {
                this.eventAreaId = newData.eventAreaId;
                this.changeEventArea();
            }
        },

        eventAreaIdFromStore(newData) {
            if (newData && newData.eventAreaId !== undefined && newData.eventAreaId !== '') {
                this.eventAreaId = newData.eventAreaId;
            }
        }
    },
    methods: {
        changeEventArea(event)
        {
            if (parseInt(this.eventAreaId) === 0) {
                this.$router.push({ name: 'grid'});
            } else {
                this.$router.push({ name: 'actionemail', params: {'id': this.eventAreaId }});
            }
        },
        closePopup(event)
        {
            if (window.parent && window.parent.$) {
                window.parent.$('.__tpopup').dialog('close');
            }
        },
    },
    async created(){
        if (typeof this.localeMapping[this.locale] !== 'undefined') {
            this.$i18n.locale = this.localeMapping[this.locale];
        }
        const result = await this.$store.dispatch('getGeneralScreenData', {'router':this.$router});
        if (result.status === 'error') {
            showMessage('Please re-login', true, 3000);
        } else {
            this.loadedScreen = true;
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
