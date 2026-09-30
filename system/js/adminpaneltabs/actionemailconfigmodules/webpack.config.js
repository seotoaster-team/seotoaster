var path = require('path');
var webpack = require('webpack');
const { VueLoaderPlugin } = require('vue-loader');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

module.exports = {
    entry: {
        actionemailconfig: __dirname + "/actionemailconfig/",
    },
    output: {
        path: __dirname + '/build/',
        filename: '[name].[contenthash].js',
        library: '[name]',
        clean: true
    },
    module: {
        rules: [
            {
                test: /\.vue$/,
                loader: 'vue-loader'

            },
            {
                test: /\.mjs$/,
                include: /node_modules/,
                type: "javascript/auto"
            },
            {
                test: /\.js$/,
                loader: 'babel-loader',
                exclude: /node_modules/
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                },
            },
            {
                resourceQuery: /blockType=i18n/,
                loader: '@kazupon/vue-i18n-loader'
            },
            {
                test: /\.css$/i,
                use: ["style-loader", "css-loader"],
            },
        ]
    },
    watch: process.env.NODE_ENV === 'development',
    watchOptions: {
        poll: true,
        aggregateTimeout: 100
    },
    devServer: {
        historyApiFallback: true,
        noInfo: true
    },
    performance: {
        hints: false
    },
    devtool: 'eval-cheap-module-source-map'
};

module.exports.plugins = [new VueLoaderPlugin(),

    new WebpackManifestPlugin({useEntryKeys: true}),

    new webpack.ProvidePlugin({
        lodash: 'lodash'
    })
]

if (process.env.NODE_ENV === 'production') {
    delete(module.exports.devtool);
    //http://vue-loader.vuejs.org/en/workflow/production.html
    module.exports.plugins = (module.exports.plugins || []).concat([
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.LoaderOptionsPlugin({
            minimize: true
        }),
    ])
}
