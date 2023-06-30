var gulp = require('gulp'),
    elixir = require('laravel-elixir');

    elixir(function(mix) {
        mix.less('style.less');
    });
    elixir(function(mix) {
        mix.less('imprimir.less');
    });