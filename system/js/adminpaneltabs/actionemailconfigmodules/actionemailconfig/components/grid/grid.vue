<template>
    <div v-show="loadedScreen" class="flex-row">
        <div id="lead-block" class="flex-row flex_12 f-alpha f-omega">
          info here
        </div>

        <router-view :key="$route.path"></router-view>
    </div>
</template>

<script src="./controller/grid.js"/>




