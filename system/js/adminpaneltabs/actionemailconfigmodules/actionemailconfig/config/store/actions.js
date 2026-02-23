export const getGeneralScreenData = ({commit, state, dispatch}, payload) => {
    return new Promise((resolve, reject) => {
        showSpinner('#ations-triggers-frm', 'action-emails-config-block-spinner system-spinner');
        $.ajax({
            'url': $('#website_url').val()+'api/toaster/actionemailgridinfo/',
            'type': 'GET',
            'dataType': 'json',
            'data': {}
        }).done(async  function(response){
            hideSpinner('.action-emails-config-block-spinner');
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

export const getDetailedScreenInfo = ({commit, state, dispatch}, payload) => {
    return new Promise((resolve, reject) => {
        showSpinner('#config-detailed-view', 'config-detailed-view-spinner system-spinner');
        $.ajax({
            'url': $('#website_url').val()+'api/toaster/actionemailgridinfo/',
            'type': 'GET',
            'dataType': 'json',
            'data': {
                'id': payload.id,
                'isGrid': 1
            }
        }).done(async  function(response){
            hideSpinner('.config-detailed-view-spinner');
            if (response.status !== 'error') {
                commit('setAdditionalInfoDetailedScreen', response.additionalInfo);
                commit('setDetailedScreenConfigData', response.data);
                resolve(response);
            } else {
                resolve({ error: 1});
            }
        }).fail(async function(response){
            resolve({ error: 1});
        });
    });
};

