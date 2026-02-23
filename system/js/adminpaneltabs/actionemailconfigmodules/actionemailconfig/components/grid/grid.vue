<template>
    <template v-if="loadedScreen">
        <div class="header">
          <div class="closebutton">
            <a class="help ticon-help" href="https://help.seosamba.com/action-emails-cheat-sheet.html" target="_blank"></a>
            <a @click="closePopup" class="close ticon-close" href="javascript:;" title="Close"></a>
          </div>
          {{$t('message.tabHeaderMessageText')}}
          <select name="actions" id="actions" class="fl-none grid_4">
            <option value="0">{{$t('message.selectEventArea')}}</option>
            <option v-for="selectionOptions in additionalInfo.presortedActionOptions" :value="selectionOptions.key">{{selectionOptions.label}}</option>
          </select>
        </div>

        <form class="f-content" action="javascript:;" method="post" id="ations-triggers-frm">
          <div class="grid_12" id="action-triggers">
            <p>
              {{$t('message.actionEmailInfoTextInfoBox')}}
            </p>
            <ul class="actions-list list-unstyled column_4 full-width">
              <li v-for="selectionOptions in additionalInfo.presortedActionOptions">
                <span class="action-preview ticon-action-emails" :data-action="formatAction(selectionOptions.key)"></span>
                <span class="action-name">{{selectionOptions.label}}</span>
              </li>
            </ul>
          </div>
        </form>
<!--        <div class="f-footer">-->
<!--          <div class="grid_12">-->
<!--            <button id="save-actions" class="btn ticon-save" name="saveActions" type="submit">{{$t('message.saveChanges')}}</button>-->
<!--          </div>-->
<!--        </div>-->

        <router-view :key="$route.path"></router-view>
    </template>
</template>

<script src="./controller/grid.js"/>




