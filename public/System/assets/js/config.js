/**
 * Config
 * -------------------------------------------------------------------------------------
 * ! IMPORTANT: Make sure you clear the browser local storage In order to see the config changes in the template.
 * ! To clear local storage: (https://www.leadshook.com/help/how-to-clear-local-storage-in-google-chrome-browser/).
 */

'use strict';

const brRoot = document.documentElement;
const brRootStyles = window.getComputedStyle(brRoot);

const brCssToken = (name, fallback = '') => brRootStyles.getPropertyValue(name).trim() || fallback;

const brColorWithAlpha = (color, alphaHex = '29') => {
  const normalized = color.trim();
  const shortHex = normalized.match(/^#([0-9a-f]{3})$/i);
  const longHex = normalized.match(/^#([0-9a-f]{6})$/i);

  if (longHex) return `${normalized}${alphaHex}`;

  if (shortHex) {
    const expanded = shortHex[1]
      .split('')
      .map(part => part + part)
      .join('');

    return `#${expanded}${alphaHex}`;
  }

  const alphaPercent = Math.round((parseInt(alphaHex, 16) / 255) * 100);

  return `color-mix(in srgb, ${normalized} ${alphaPercent}%, transparent)`;
};

const brNormalizeAssetsPath = value => {
  const fallback = '/System/assets/';
  const source = (value || fallback).trim();
  const isAbsoluteUrl = /^[a-z][a-z\d+\-.]*:\/\//i.test(source) || source.startsWith('//');

  if (isAbsoluteUrl || source.startsWith('/')) {
    return new URL(source, window.location.origin).href.replace(/\/?$/, '/');
  }

  const systemAssetsMatch = source.match(/(?:^|\/)(System\/assets\/?.*)$/i);

  if (systemAssetsMatch) {
    return new URL(`/${systemAssetsMatch[1].replace(/^\/+/, '')}`, window.location.origin).href.replace(/\/?$/, '/');
  }

  return new URL(source, window.location.href).href.replace(/\/?$/, '/');
};

// JS global variables
let config = {
  typography: {
    fontFamily: brCssToken("--br-font-sans", "sans-serif")
  },
  colors: {
    primary: brCssToken('--br-primary'),
    secondary: brCssToken('--br-secondary'),
    success: brCssToken('--br-success'),
    info: brCssToken('--br-info'),
    warning: brCssToken('--br-warning'),
    danger: brCssToken('--br-danger'),
    dark: brCssToken('--br-neutral-900'),
    black: brCssToken('--br-black'),
    white: brCssToken('--br-white'),
    cardColor: brCssToken('--br-surface-elevated'),
    bodyBg: brCssToken('--br-surface'),
    bodyColor: brCssToken('--br-text'),
    headingColor: brCssToken('--br-secondary'),
    textMuted: brCssToken('--br-text-muted'),
    borderColor: brCssToken('--br-border')
  },
  colors_label: {
    primary: brColorWithAlpha(brCssToken('--br-primary')),
    secondary: brColorWithAlpha(brCssToken('--br-secondary')),
    success: brColorWithAlpha(brCssToken('--br-success')),
    info: brColorWithAlpha(brCssToken('--br-info')),
    warning: brColorWithAlpha(brCssToken('--br-warning')),
    danger: brColorWithAlpha(brCssToken('--br-danger')),
    dark: brColorWithAlpha(brCssToken('--br-neutral-900'))
  },
  colors_dark: {
    cardColor: brCssToken('--br-secondary-hover'),
    bodyBg: brCssToken('--br-secondary-active'),
    bodyColor: brCssToken('--br-secondary-soft'),
    headingColor: brCssToken('--br-on-secondary'),
    textMuted: brCssToken('--br-secondary-muted'),
    borderColor: brCssToken('--br-template-dark-border')
  },
  enableMenuLocalStorage: true // Enable menu state with local storage support
};

let assetsPath = brNormalizeAssetsPath(brRoot.getAttribute('data-assets-path')),
  templateName = document.documentElement.getAttribute('data-template'),
  rtlSupport = true; // set true for rtl support (rtl + ltr), false for ltr only.

brRoot.setAttribute('data-assets-path', assetsPath);
window.assetsPath = assetsPath;

/**
 * TemplateCustomizer
 * ! You must use(include) template-customizer.js to use TemplateCustomizer settings
 * -----------------------------------------------------------------------------------------------
 */

// To use more themes, just push it to THEMES object.

/* TemplateCustomizer.THEMES.push({
  name: 'theme-raspberry',
  title: 'Raspberry'
}); */

// To add more languages, just push it to LANGUAGES object.
/*
TemplateCustomizer.LANGUAGES.fr = { ... };
*/

/**
 * TemplateCustomizer settings
 * -------------------------------------------------------------------------------------
 * cssPath: Core CSS file path
 * themesPath: Theme CSS file path
 * displayCustomizer: true(Show customizer), false(Hide customizer)
 * lang: To set default language, Add more langues and set default. Fallback language is 'en'
 * controls: [ 'rtl', 'style', 'headerType', 'contentLayout', 'layoutCollapsed', 'layoutNavbarOptions', 'themes' ] | Show/Hide customizer controls
 * defaultTheme: 0(Default), 1(Bordered), 2(Semi Dark)
 * defaultStyle: 'light', 'dark', 'system' (Mode)
 * defaultTextDir: 'ltr', 'rtl' (rtlSupport must be true for rtl mode)
 * defaultContentLayout: 'compact', 'wide' (compact=container-xxl, wide=container-fluid)
 * defaultHeaderType: 'static', 'fixed' (for horizontal layout only)
 * defaultMenuCollapsed: true, false (For vertical layout only)
 * defaultNavbarType: 'sticky', 'static', 'hidden' (For vertical layout only)
 * defaultFooterFixed: true, false (For vertical layout only)
 * defaultShowDropdownOnHover : true, false (for horizontal layout only)
 */

if (typeof TemplateCustomizer !== 'undefined') {
  window.templateCustomizer = new TemplateCustomizer({
    cssPath: assetsPath + 'vendor/css' + (rtlSupport ? '/rtl' : '') + '/',
    themesPath: assetsPath + 'vendor/css' + (rtlSupport ? '/rtl' : '') + '/',
    displayCustomizer: false,
    lang: localStorage.getItem('templateCustomizer-' + templateName + '--Lang') || 'en', // Set default language here
    defaultTheme: 2,
    defaultStyle: 'light',
    // defaultTextDir: 'rtl',
    // defaultContentLayout: 'wide',
    // defaultHeaderType: 'static',
    // defaultMenuCollapsed: true,
    // defaultNavbarType: 'sticky',
    // defaultFooterFixed: false,
    // defaultShowDropdownOnHover: false,
    controls: ['rtl', 'style', 'headerType', 'contentLayout', 'layoutCollapsed', 'layoutNavbarOptions', 'themes']
  });
}
