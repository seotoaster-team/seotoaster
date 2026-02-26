<template>
  <div id="config-detailed-view">
    <template v-if="loadedScreen === true">
      {{ configId }}
      {{ activeTab }}
      <div id="actions-triggers-frm">
        <!-- TABS -->
        <div v-if="configId !== '0' && typeof additionalInfo.triggers[configId] !== 'undefined' && typeof additionalInfo.triggers[configId]['trigger'] !== 'undefined'">
          <div class="tabs-nav-wrap">
            <span class="arrow left ticon-arrow-left3" @click="scrollTabs('left')"></span>
            <div class="tabs-scroll" ref="tabsScroll">
              <ul class="tabs-header" id="triggers-tabs-holder">
                <li v-for="(data, name) in additionalInfo.triggers[configId]['trigger']" :key="name" :class="[activeTab === name? 'active': '']">
                  <button type="button" @click="changeTab(name)">
                    {{ data.title }}
                  </button>
                </li>
              </ul>
            </div>
            <span class="arrow right ticon-arrow-right3" @click="scrollTabs('right')"></span>
          </div>
          <!-- TAB CONTENTS -->
          <div v-for="(data, name) in additionalInfo.triggers[configId]['trigger']" :key="name" v-show="activeTab === name" class="tabs-contents">
            <!-- ADD NEW ACTION -->
            <span class="new-trigger-action" @click="addAction(name)">+</span>

            <!-- ACTION FIELDSETS -->
            <fieldset v-for="(action, index) in filteredActions(name)" :key="action.localId" v-show="action.delete !== true && action.delete !== 'true'"
                  class="background"
              >
              <span class="ticon-close" @click="remove(action.localId)"></span>
              <div class="trigger-title">
                {{$t('message.when')}} <strong>{{ data.title }}</strong>
              </div>
              <!-- SERVICE SELECT -->
              <div v-if="showServiceSelector(name, data)">
                <label>{{$t('message.send')}}</label>
                <select v-model="action.service" @change="onServiceChange(action)">
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
                <label>{{$t('message.sendTo')}}</label>
                <select v-model="action.recipient">
                  <option v-for="recipient in filteredRecipients(action)" :key="recipient.value" :value="recipient.value">
                    {{ recipient.label }}
                  </option>
                </select>
              </div>

              <!-- TEMPLATE -->
              <div v-if="action.service !== 'sms'">
                <label>{{$t('message.useTemplate')}}</label>
                <select v-model="action.template">
                  <option v-for="tpl in processedMailTemplates" :key="tpl.value" :value="tpl.value">
                    {{ tpl.label }}
                  </option>
                </select>
              </div>

              <!-- MESSAGE (EMAIL) -->
              <div v-if="action.service !== 'sms'">
                <label>{{$t('message.withMessage')}}</label>
                <textarea v-model="action.message" rows="4"></textarea>
              </div>

              <!-- FROM -->
              <div v-if="action.service !== 'sms'">
                <label>{{$t('message.from')}}</label>
                <input type="text" v-model="action.from">
              </div>

              <!-- SUBJECT -->
              <div v-if="action.service !== 'sms'">
                <label>{{$t('message.withSubject')}}</label>
                <input type="text" v-model="action.subject">
              </div>

              <!-- PREHEADER -->
              <div v-if="action.service !== 'sms' && typeof data.preheader !== 'undefined'">
                <label>
                  {{$t('message.preheader')}}
                  <span class="ticon-info tooltip icon18" :title="$t('message.preheaderTooltip')"></span>
                </label>
                <input type="text" v-model="action.preheader">
              </div>

              <!-- SMS TEXT -->
              <div v-if="name === 'store_neworder' || name === 'store_trackingnumber' || typeof data.withsms !== 'undefined'" :class="{ 'hide': action.service !== 'sms' }">
                <label>{{$t('message.insertPlainText')}}</label>
                <textarea v-model="action.message" rows="5" class="grid_12" :placeholder="$t('message.smsTextOnly')" style="height:172px;"></textarea>
              </div>
            </fieldset>
          </div>
        </div>
        <button v-if="parseInt(configId) !== 0" @click="saveAction" id="save-actions">{{$t('message.save')}}</button>
      </div>
    </template>
    <router-view :key="$route.path"></router-view>
  </div>
</template>

<script src="./controller/actionemailinfo.js"/>




