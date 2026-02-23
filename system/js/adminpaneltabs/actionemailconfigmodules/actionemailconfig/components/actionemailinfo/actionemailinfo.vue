<template>
    <div id="config-detailed-view">
      <template v-if="loadedScreen === true">
        {{configId}}
      </template>
      <router-view :key="$route.path"></router-view>
    </div>
</template>

<script src="./controller/actionemailinfo.js"/>




