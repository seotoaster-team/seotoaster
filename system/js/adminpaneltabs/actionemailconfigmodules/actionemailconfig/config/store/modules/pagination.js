import merge from 'lodash/merge';
const state = {
    leadGrid: {
        currentPage: 1,
        itemsPerPage: 10,
        totalItems: 0,
        visiblePages: 4
    },
    timelineLog: {
        currentPage: 1,
        itemsPerPage: 10,
        totalItems: 0,
        visiblePages: 4
    },
    profileLog: {
        currentPage: 1,
        itemsPerPage: 10,
        totalItems: 0,
        visiblePages: 4
    },
    opportunitiesLog: {
        currentPage: 1,
        itemsPerPage: 10,
        totalItems: 0,
        visiblePages: 4
    },
    emailsSmsLog: {
        currentPage: 1,
        itemsPerPage: 10,
        totalItems: 0,
        visiblePages: 4
    },
    documentsGrid: {
        currentPage: 1,
        itemsPerPage: 5,
        totalItems: 0,
        visiblePages: 4
    }
};
const getters = {
    getPagerState: (state) => {
        return (sectionName) => {
            return state[sectionName];
        }
    }
};
const actions = {};

const mutations = {
    setPaginationData: (state, payload) => {
        merge(state, payload);
    }
};

export default {
    state,
    getters,
    actions,
    mutations
};
