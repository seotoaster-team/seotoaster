<template>
    <div v-if="loadedScreen === true" id="lead-block-detailed-view">
        <router-view :key="$route.path"></router-view>
    </div>
</template>

<script src="./controller/actionemailinfo.js"/>




