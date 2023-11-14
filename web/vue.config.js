// vue.config.js
var webpack = require('webpack');
const CompressionPlugin = require("compression-webpack-plugin");
const productionGzipExtensions = /\.(js|css|json|txt|html|ico|svg)(\?.*)?$/i;

module.exports = {
    // 修改的配置
    // 将baseUrl: '/api',改为baseUrl: '/',
    // baseUrl: '/api',
    devServer: {
      disableHostCheck: true,
        proxy: {
            "/api": {
                // target: 'http://es360feed.com/api',
                target: "http://0.0.0.0:8787/api",
                changeOrigin: true,
                logger: console,
                logLevel: 'debug',
                ws: true,
                pathRewrite: {
                  "^/api": ""
                }
            }
        }
    },
    productionSourceMap: false,
    configureWebpack: config => {
      if (process.env.NODE_ENV === "production") {
        return {
          plugins: [
            new CompressionPlugin({
              filename: "[path].gz[query]",
              algorithm: "gzip",
              test: productionGzipExtensions,
              threshold: 10240,
              minRatio: 0.8,
              deleteOriginalAssets: false,
            }),
            new webpack.optimize.MinChunkSizePlugin({
              minChunkSize: 20000 // Minimum number of characters
            })
          ],
        }
      }
    },

    chainWebpack: config => {
      config
        .plugin('html')
        .tap(args => {
          args[0].title= 'FadeTask | 任务管理 | 项目管理 | 敏捷看板'
          return args
        })
    },
}
// .env.development
// VUE_APP_BASE_API="/api"