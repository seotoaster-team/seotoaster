export const getGeneralScreenData = ({commit, state, dispatch}, payload) => {
    return new Promise((resolve, reject) => {
        showSpinner('#action-emails-config-block', 'action-emails-config-block-spinner system-spinner');
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
        showSpinner('#actions-triggers-frm', 'config-detailed-view-spinner system-spinner');
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

export const saveTriggerActions = ({commit, state, dispatch}, payload) => {
    showSpinner('#tabs-content-block', 'actions-triggers-frm-spinner system-spinner');
    return new Promise((resolve, reject) => {
        $.ajax({
            'url': $('#website_url').val()+'backend/backend_config/actionmails/',
            'type': 'POST',
            'dataType': 'json',
            'data': {
                'secureToken':$('#action-emails-config-screen-token').val(),
                'actions':payload.actionsPayload,
                'returnActionsList':'1'
            }
        }).done(async  function(response){
            hideSpinner('.actions-triggers-frm-spinner');
            if (response.status !== 'error') {
                resolve(response);
            } else {
                resolve({ error: 1, message:response});
            }
        }).fail(async function(response){
            hideSpinner('.actions-triggers-frm-spinner');
            resolve({ error: 1, message:response.responseJSON});
        });
    });
};

