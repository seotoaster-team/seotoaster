import { createStore } from 'vuex';
import * as actions from './actions.js';
import * as modules from './modules/index';

export const store = createStore({
    state: {
        userId: []
    },
    modules:{
        ...modules
    },
    actions
})