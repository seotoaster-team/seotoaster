<template>
<!--  <div id="actions-triggers-frm" class="f-wrapper">-->
    <template v-if="loadedScreen === true">
        <!-- TABS -->
        <template v-if="configId !== '0' && typeof additionalInfo.triggers[configId] !== 'undefined' && typeof additionalInfo.triggers[configId]['trigger'] !== 'undefined'">
          <div class="tabs-nav-wrap">
            <span class="arrow left ticon-arrow-left3" @click="scrollTabs('left')"></span>
            <div class="tabs-scroll" ref="tabsScroll">
              <ul class="tabs-header" id="triggers-tabs-holder">
                <li v-for="name in tabNames" :key="name" :class="[activeTab === name ? 'active' : '']">
                  <button type="button" @click="changeTab(name)">
                    {{ additionalInfo.triggers[configId]['trigger'][name].title }}
                  </button>
                </li>
                <li class="tabs-end-spacer"></li>
              </ul>
            </div>
            <span class="arrow right ticon-arrow-right3" @click="scrollTabs('right')"></span>
          </div>
          <!-- TAB CONTENTS -->
          <div id="tabs-content-block" class="f-content" style="position: relative;">
            <div v-for="(data, name) in additionalInfo.triggers[configId]['trigger']" :key="name" v-show="activeTab === name" class="tabs-contents">
              <!-- ACTION FIELDSETS -->
              <fieldset v-for="(action, index) in filteredActions(name)" :key="action.localId" v-show="action.delete !== true && action.delete !== 'true'" class="background form">
                <span class="ticon-close btn icon link error" @click="remove(action.localId)"></span>
                <div class="trigger-title">
                  {{$t('message.when')}} <strong>{{ data.title }}</strong>
                </div>
                <!-- SERVICE SELECT -->
                <div v-if="showServiceSelector(name, data)">
                  <label class="grid_4 mt5px">{{$t('message.send')}}</label>
                  <select class="grid_8" name="services-list" v-model="action.service" @change="onServiceChange(action)">
                    <option v-for="service in processedServices" :key="service.value" :value="service.value">
                      {{ service.label }}
                    </option>
                  </select>
                </div>
                <div v-else>
                  <input type="hidden" v-model="action.service" value="email">
                </div>

                <!-- RECIPIENT -->
                <div>
                  <label class="grid_4 mt5px">{{$t('message.sendTo')}}</label>
                  <select class="grid_8" v-model="action.recipient">
                    <option v-for="recipient in filteredRecipients(action)" :key="recipient.value" :value="recipient.value">
                      {{ recipient.label }}
                    </option>
                  </select>
                </div>

                <!-- TEMPLATE -->
                <div v-if="action.service !== 'sms'">
                  <label class="grid_4 mt5px">{{$t('message.useTemplate')}}</label>
                  <select class="grid_8" v-model="action.template">
                    <option v-for="tpl in processedMailTemplates" :key="tpl.value" :value="tpl.value">
                      {{ tpl.label }}
                    </option>
                  </select>
                </div>

                <!-- MESSAGE (EMAIL) -->
                <div v-if="action.service !== 'sms'">
                  <label class="grid_4 mt5px">{{$t('message.withMessage')}}</label>
                  <textarea class="grid_8" v-model="action.message" rows="4"></textarea>
                </div>

                <!-- FROM -->
                <div v-if="action.service !== 'sms'">
                  <label class="grid_4 mt5px">{{$t('message.from')}}</label>
                  <input class="grid_8" type="text" v-model="action.from">
                </div>

                <!-- SUBJECT -->
                <div v-if="action.service !== 'sms'">
                  <label class="grid_4 mt5px">{{$t('message.withSubject')}}</label>
                  <input class="grid_8" type="text" v-model="action.subject">
                </div>

                <!-- PREHEADER -->
                <div v-if="action.service !== 'sms' && typeof data.preheader !== 'undefined' && typeof data.withsms === 'undefined'">
                  <label class="grid_4 mt5px">
                    {{$t('message.preheader')}}
                    <span class="ticon-info tooltip icon18" :title="$t('message.preheaderTooltip')"></span>
                  </label>
                  <input class="grid_8" type="text" v-model="action.preheader">
                </div>

                <!-- SMS TEXT -->
                <div v-if="name === 'store_neworder' || name === 'store_trackingnumber' || typeof data.withsms !== 'undefined'" :class="{ 'hide': action.service !== 'sms' }">
                  <label class="grid_4 mt5px">{{$t('message.insertPlainText')}}</label>
                  <textarea v-model="action.message" rows="5" class="grid_8" :placeholder="$t('message.smsTextOnly')" style="height:172px;"></textarea>
                </div>
              </fieldset>
              <!-- ADD NEW ACTION -->
              <span class="new-trigger-action ticon-plus-sign text-center" @click="addAction(name)"></span>
            </div>
          </div>
        </template>
        <div class="f-footer">
          <div class="grid_12">
            <button v-if="parseInt(configId) !== 0" @click="saveAction" id="save-actions" class="btn success save-and-close">{{$t('message.saveChanges')}}</button>
          </div>
        </div>
    </template>
    <router-view :key="$route.path"></router-view>
<!--  </div>-->
</template>

<script src="./controller/actionemailinfo.js"/>

<style>
/*.tabs-scroll {*/
/*  overflow-x: auto;*/
/*  white-space: nowrap;*/
/*  padding-right: 100px; !* space so last tab is visible *!*/
/*  box-sizing: content-box;*/
/*}*/

/*.tabs-header {*/
/*  display: flex;*/
/*}*/

/*.tabs-header li {*/
/*  background: #e9e9e9;*/
/*  border: 1px solid #d0d0d0;*/
/*  border-bottom: none;*/
/*}*/

/*.tabs-header li.active {*/
/*  background: #ffffff;*/
/*  font-weight: bold;*/
/*}*/

/*.tabs-end-spacer {*/
/*  width: 30px; !* slightly more than arrow width + buffer *!*/
/*  flex-shrink: 0;*/
/*}*/

/*!* optional small spacing so last tab doesn’t touch edge *!*/
/*.tabs-header li:last-child {*/
/*  margin-right: 10px;*/
/*}*/
/*.tabs-scroll::-webkit-scrollbar {*/
/*  display: none;*/
/*}*/

/*!* Hide scrollbar for Firefox *!*/
/*.tabs-scroll {*/
/*  scrollbar-width: none; !* Firefox *!*/
/*  -ms-overflow-style: none; !* IE 10+ *!*/
/*}*/
</style>


