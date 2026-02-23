<template>
    <div class="header">
      <div class="closebutton">
        <a class="help ticon-help" href="https://help.seosamba.com/action-emails-cheat-sheet.html" target="_blank"></a>
        <a @click="closePopup" class="close ticon-close" href="javascript:;" title="Close"></a>
      </div>
      {{$t('message.tabHeaderMessageText')}}
      <select @change="changeEventArea" v-model="eventAreaId" name="actions" id="actions" class="fl-none grid_4">
        <option value="0">{{$t('message.selectEventArea')}}</option>
        <option v-for="selectionOptions in additionalInfo.presortedActionOptions" :value="selectionOptions.key">{{selectionOptions.label}}</option>
      </select>
    </div>
     <router-view :key="$route.path"></router-view>
</template>

<script src="./controller/mainscreen.js"/>




