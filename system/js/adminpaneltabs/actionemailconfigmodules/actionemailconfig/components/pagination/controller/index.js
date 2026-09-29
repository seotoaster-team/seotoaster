import {mapGetters} from 'vuex';
export default {
    props: ['sectionName', 'perPageDropdown'],
    computed: {
        ...mapGetters({
            pagerState: 'getPagerState',
        }),

        lastPage () {
            return this.pagerState(this.sectionName).totalItems % this.pagerState(this.sectionName).itemsPerPage === 0
                ? this.pagerState(this.sectionName).totalItems / this.pagerState(this.sectionName).itemsPerPage
                : Math.floor(this.pagerState(this.sectionName).totalItems / this.pagerState(this.sectionName).itemsPerPage) + 1
        },

        paginationRange(){
            let start =
                this.pagerState(this.sectionName).currentPage - this.pagerState(this.sectionName).visiblePages / 2 <= 0
                    ? 1 : this.pagerState(this.sectionName).currentPage + this.pagerState(this.sectionName).visiblePages / 2 > this.lastPage
                    ? lowerBound(this.lastPage - this.pagerState(this.sectionName).visiblePages + 1, 1)
                    : Math.ceil(this.pagerState(this.sectionName).currentPage - this.pagerState(this.sectionName).visiblePages / 2);
            let range = [];
            for (let i = 0; i < this.pagerState(this.sectionName).visiblePages && i < this.lastPage; i++) {
                range.push(start + i)
            }
            return range
        },
        rowPerPage: {
            get(){
                return this.pagerState(this.sectionName).itemsPerPage;
            },
            async set(value){
                this.$store.commit('setPaginationData', {[this.sectionName] :{itemsPerPage: value, currentPage: 1}});
                showLoader();
                this.$emit('paginationHandler');
                hideLoader();
            }
        }
    },

    methods: {
        activePage (pageNum) {
            if(this.lastPage < this.pagerState(this.sectionName).currentPage){
                this.$store.commit('setPaginationData', {[this.sectionName] : {currentPage: this.lastPage}});
            }
            return this.pagerState(this.sectionName).currentPage === pageNum ? ' active error' : ''
        },

        async pageChanged (pageNum) {
            this.$store.commit('setPaginationData', {[this.sectionName] : {currentPage: pageNum}});
            showLoader();
            this.$emit('paginationHandler');
            hideLoader();
        }
    }
}

const lowerBound = (num, limit) =>  {
    return num >= limit ? num : limit;
}