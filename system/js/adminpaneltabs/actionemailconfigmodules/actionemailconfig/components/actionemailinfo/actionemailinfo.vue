<template>
  <div id="config-detailed-view">
    <template v-if="loadedScreen === true">
      {{ configId }}
      <div id="action-triggers">

        <!-- TABS -->
        <div v-if="currentTrigger && triggers[currentTrigger]">

          <div class="tabs-nav-wrap">
            <ul class="header">
              <li v-for="(data, name) in triggers[currentTrigger]"
                  :key="name">
                <a href="#"
                   @click.prevent="activeTab = name">
                  {{ data.title }}
                </a>
              </li>
            </ul>
          </div>

          <!-- TAB CONTENTS -->
          <div v-for="(data, name) in triggers[currentTrigger]"
               :key="name"
               v-show="activeTab === name"
               class="tabs-contents">

            <!-- ADD NEW ACTION -->
            <span class="new-trigger-action"
                  @click="addAction(name)">
          +
        </span>

            <!-- ACTION FIELDSETS -->
            <fieldset v-for="(action, index) in filteredActions(name)"
                      :key="action.localId"
                      class="background">

          <span class="ticon-close"
                @click="remove(index)">
            ✕
          </span>

              <div class="trigger-title">
                When <strong>{{ data.title }}</strong>
              </div>

              <!-- SERVICE SELECT -->
              <div v-if="showServiceSelector(name, data)">
                <label>Send</label>
                <select v-model="action.service">
                  <option v-for="service in services"
                          :key="service.value"
                          :value="service.value">
                    {{ service.label }}
                  </option>
                </select>
              </div>

              <!-- RECIPIENT -->
              <div>
                <label>Send to</label>
                <select v-model="action.recipient">
                  <option v-for="recipient in filteredRecipients(action)"
                          :key="recipient.value"
                          :value="recipient.value">
                    {{ recipient.label }}
                  </option>
                </select>
              </div>

              <!-- TEMPLATE -->
              <div v-if="action.service !== 'sms'">
                <label>Use template</label>
                <select v-model="action.template">
                  <option v-for="tpl in mailTemplates"
                          :key="tpl.value"
                          :value="tpl.value">
                    {{ tpl.label }}
                  </option>
                </select>
              </div>

              <!-- MESSAGE (EMAIL) -->
              <div v-if="action.service !== 'sms'">
                <label>With message</label>
                <textarea v-model="action.message"
                          rows="4"></textarea>
              </div>

              <!-- FROM -->
              <div v-if="action.service !== 'sms'">
                <label>From</label>
                <input type="text"
                       v-model="action.from">
              </div>

              <!-- SUBJECT -->
              <div v-if="action.service !== 'sms'">
                <label>With subject</label>
                <input type="text"
                       v-model="action.subject">
              </div>

              <!-- PREHEADER -->
              <div v-if="action.service !== 'sms' && data.preheader !== undefined">
                <label>Preheader</label>
                <input type="text"
                       v-model="action.preheader">
              </div>

              <!-- SMS TEXT -->
              <div v-if="action.service === 'sms'">
                <label>Insert plain text message only</label>
                <textarea v-model="action.message"
                          rows="5"
                          placeholder="SMS are text only">
            </textarea>
              </div>

            </fieldset>

          </div>
        </div>
        <button v-if="currentTrigger !== '0'"
                @click="saveAction"
                id="save-actions">
          Save
        </button>

      </div>

    </template>
    <router-view :key="$route.path"></router-view>
  </div>
</template>

<script src="./controller/actionemailinfo.js"/>




