import {defineConfig} from 'vitepress'

export default defineConfig({
  title: 'Instant Analytics GA4 Plugin',
  description: 'Documentation for the Instant Analytics GA4 plugin',
  base: '/docs/instant-analytics-ga4/',
  lang: 'en-US',
  head: [
    ['meta', {content: 'https://github.com/nystudio107', property: 'og:see_also',}],
    ['meta', {content: 'https://twitter.com/nystudio107', property: 'og:see_also',}],
    ['meta', {content: 'https://youtube.com/nystudio107', property: 'og:see_also',}],
    ['meta', {content: 'https://www.facebook.com/newyorkstudio107', property: 'og:see_also',}],
  ],
  themeConfig: {
    socialLinks: [
      {icon: 'github', link: 'https://github.com/nystudio107'},
      {icon: 'twitter', link: 'https://twitter.com/nystudio107'},
    ],
    logo: '/img/plugin-logo.svg',
    editLink: {
      pattern: 'https://github.com/nystudio107/craft-instantanalytics-ga4/edit/develop-v4/docs/docs/:path',
      text: 'Edit this page on GitHub'
    },
    algolia: {
      appId: '',
      apiKey: '',
      indexName: 'instant-analytics-ga4'
    },
    lastUpdatedText: 'Last Updated',
    sidebar: [
      {
        text: 'Topics',
        items: [
          {text: 'Instant Analytics GA4 Plugin', link: '/'},
          {text: 'Instant Analytics GA4 Overview', link: '/overview.html'},
          {text: 'Use Cases', link: '/use-cases.html'},
          {text: 'Configuring Instant Analytics GA4', link: '/configuring.html'},
          {text: 'Using Instant Analytics GA4', link: '/using.html'},
        ],
      }
    ],
    nav: [
      {text: 'Home', link: 'https://nystudio107.com/plugins/instant-analytics-ga4'},
      {text: 'Store', link: 'https://plugins.craftcms.com/instant-analytics-ga4'},
      {text: 'Changelog', link: 'https://nystudio107.com/plugins/instant-analytics-ga4/changelog'},
      {text: 'Issues', link: 'https://github.com/nystudio107/craft-instantanalytics-ga4/issues'},
      {
        text: 'v4', items: [
          {text: 'v4', link: '/'},
        ],
      },
    ],
  },
});
