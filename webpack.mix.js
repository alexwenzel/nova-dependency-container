let mix = require('laravel-mix')
let path = require('path')

require('./nova.mix')

mix
    .setPublicPath('dist')
    .js('resources/js/field.js', 'js')
    .vue({version: 3})
    .webpackConfig({
        // stats: { children: true },
        externals: {
            vue: 'Vue',
            'laravel-nova': 'LaravelNova',
        }
    })
    .nova('alexwenzel/dependency-container')
