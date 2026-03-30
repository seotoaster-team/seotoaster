import merge from 'lodash/merge';
import moment from 'moment';
import lodash from 'lodash';
import unescape from "lodash/unescape";
import {toRaw} from "vue";

let defaultState = {
    configDataInfo: [],
    additionalInfo: [],
    selectedUserId: '',
    currencyInfo :[],
    additionalInfoDetailedScreen :[],
    detailedScreenConfigData :[],
    changeEventAreaRemote:0,
    eventAreaId:0
};

let state = {};
merge(state, defaultState);

const actions = {

};

const mutations = {
    setConfigDataInfo : (state, payload) => {
        state.configDataInfo = payload;
    },
    setAdditionalInfo : (state, payload) => {
        state.additionalInfo = payload;
    },
    setCurrencyInfo: (state, payload) => {
        state.currencyInfo = payload
    },
    setAdditionalInfoDetailedScreen:(state, payload) => {
        state.additionalInfoDetailedScreen = payload;
    },
    setDetailedScreenConfigData:(state, payload) => {
        state.detailedScreenConfigData = payload;
    },
    setChangeEventAreaRemote:(state, payload) => {
        state.changeEventAreaRemote = payload;
    },
    setEventAreaId:(state, payload) => {
        state.eventAreaId = payload;
    },

};

const getters = {
    getConfigDataInfo : (state) => {
        return state.configDataInfo
    },
    getAdditionalInfo : (state) => {
        return state.additionalInfo
    },
    getCurrencyInfo : (state) => {
        return state.currencyInfo
    },
    getChangeFilter : (state) => {
        return state.filterInfo
    },
    getAdditionalInfoDetailedScreen : (state) => {
        return state.additionalInfoDetailedScreen
    },
    getDetailedScreenConfigData : (state) => {
        return state.detailedScreenConfigData
    },
    getChangeEventAreaRemote : (state) => {
        return state.changeEventAreaRemote
    },
    getEventAreaId : (state) => {
        return state.eventAreaId
    },
    formatDate : (state) => {
        return (date) => {
            let finalDate =  moment(date).format('DD MMM YYYY hh:mm a');
            if (finalDate === 'Invalid date') {
                return '';
            }

            return finalDate;
        }
    },
    formatOnlyDate: (state) => {
        return (date) => {
            let finalDate = moment(date).format('DD MMM YYYY');
            if (finalDate === 'Invalid date') {
                return '';
            }

            return finalDate;
        }
    },
    formatTimeDayFullName: (state) => {
        return (date) => {
            let finalDate = moment(date).format('DD MMMM YYYY');
            if (finalDate === 'Invalid date') {
                return '';
            }

            return finalDate;
        }
    },
    formatTimeOnlyWithPartOfTheDay: (state) => {
        return (date) => {
            let finalDate = moment(date).format('hh:mm A');
            if (finalDate === 'Invalid date') {
                return '';
            }

            return finalDate;
        }
    },
    formatTimeOnly: (state) => {
        return (date) => {
            let finalDate = moment(date).format('HH:mm');
            if (finalDate === 'Invalid date') {
                return '';
            }

            return finalDate;
        }
    },
    isValidDate: (state) => {
        return (date) => {
            return moment(date, 'YYYY-MM-DD HH:mm:ss').isValid();
        }
    },
    isAfterDate: (state) => {
        return (date, dateCompare) => {
            let origDate = moment(date).format('DD MMM YYYY');
            if (origDate === 'Invalid date') {
                return '';
            }

            let compareDate = moment(dateCompare).format('DD MMM YYYY');
            if (compareDate === 'Invalid date') {
                return '';
            }

            return moment(origDate).isAfter(compareDate);
        }
    },
    sortByColumn : (state) => {
        return (data, columnName, reverse, numerical) => {
            if (reverse) {
                return _.orderBy(data, [info => info[columnName].toLowerCase()]).reverse();
            }

            if (numerical) {
                return   _.orderBy(data,  [info => parseInt(info[columnName])]);
            }

            return _.orderBy(data,  [info => info[columnName].toLowerCase()]);
        }
    },
    truncateText : (state) => {
        return (text, limit) => {
            if (text.length > limit) {
                text = text.substring(0, (limit - 3)) + '...';
            }

            return text;
        }
    },
    cleanText : (state) => {
        return (text, replaceTo) => {
            let replaceToSymbol = '';

            if (typeof replaceTo !== 'undefined') {
                replaceToSymbol = replaceTo;
            }

            text = text.replace(/(<([^>]+)>)/gi, replaceToSymbol);

            return text;
        }
    },
    countOnlySymbols : (state) => {
        return (text) => {
            let replaceToSymbol = text.replaceAll(/[.,?!;:\-—\[\]{}() ]/g, "").length;

            return replaceToSymbol;
        }
    },
    toCurrency : (state) => {
        return (value, decimals) => {
            let result = parseFloat(value),
                minDecimals = 2;

            if (typeof decimals !== 'undefined') {
                minDecimals = decimals;
            }

            if (isNaN(result)) {
                return '';
            }

            if (state.currencyInfo) {
                result = result.toLocaleString(state.currencyInfo.locale, { style: 'currency', currency: state.currencyInfo.currency, minimumFractionDigits: minDecimals, maximumFractionDigits: minDecimals });
            }

            return result;
        }
    },
    currencyOnly: (state) => {
       return (value) => {
          let result ='';

          result = (0).toLocaleString(state.currencyInfo.locale, { style: 'currency', currency:state.currencyInfo.currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).replace(/\d/g, '').trim()

          return result;
      }
    },
    unescapeValue: (state) => {
        return (value) => {
            return unescape(value);
        }
    },
    alphabeticalSort: (state) => {
        return (data) => {
            // convert object into array
            var sortable=[];
            for(var key in data)
            if(data.hasOwnProperty(key))
                sortable.push([key, data[key]]); // each item is an array in format [key, value]
            // sort items by value
            sortable.sort(function(a, b)
            {
                var x=a[1].toLowerCase(),
                    y=b[1].toLowerCase();
                return x<y ? -1 : x>y ? 1 : 0;
            });
            return sortable; // array in format [ [ key1, val1 ], [ key2, val2 ], ... ]
        }
    },
    ucFirstAllText: (state) => {
        return (str) => {
            let result = '';
            for (let i = 0; i < str.length; i += 1) {
                let shouldBeBig = str[i] !== ' ' && (i === 0 || str[i - 1] === ' ');
                result += shouldBeBig ? str[i].toUpperCase() : str[i];
            }
            return result;
        };
    },


};
export default {
    state,
    actions,
    getters,
    mutations
};
