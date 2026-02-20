export const getGeneralLeadsScreenData = ({commit, state, dispatch}, payload) => {
    return new Promise((resolve, reject) => {
        showSpinner('#leads-screen-config-block', 'leads-screen-config-block-spinner dashboard-spinner');
        $.ajax({
            'url': $('#website_url').val()+'api/leads/leadgridinfo/',
            'type': 'GET',
            'dataType': 'json',
            'data': {}
        }).done(async  function(response){
            hideSpinner('.leads-screen-config-block-spinner');
            if (response.status !== 'error') {
                commit('setConfigDataInfo', response.data);
                commit('setAdditionalInfo', response.additionalInfo);
                resolve(response);
            } else {
                resolve({ name: 'login', 'message': 'Please re-login'});
            }
        }).fail(async function(response){
            resolve({ name: 'login', 'message': 'Please re-login'});
        });
    });
};


