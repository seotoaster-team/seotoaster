<template>
    <template v-if="loadedScreen">
        <form class="f-content" action="javascript:;" method="post" id="ations-triggers-frm">
          <div class="grid_12" id="action-triggers">
            <p>
              {{$t('message.actionEmailInfoTextInfoBox')}}
            </p>
            <ul class="actions-list list-unstyled column_4 full-width">
              <li @click="goToDetailsScreen(selectionOptions.key)" v-for="selectionOptions in additionalInfo.presortedActionOptions">
                <span class="action-preview ticon-action-emails" :data-action="formatAction(selectionOptions.key)"></span>
                <span class="action-name">{{selectionOptions.label}}</span>
              </li>
            </ul>
          </div>
        </form>
        <router-view :key="$route.path"></router-view>
    </template>
</template>

<script src="./controller/grid.js"/>




