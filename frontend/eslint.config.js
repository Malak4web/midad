import pluginVue from 'eslint-plugin-vue'

export default [
  { ignores: ['dist/**', 'coverage/**', 'playwright-report/**', 'test-results/**'] },
  ...pluginVue.configs['flat/recommended'],
  {
    rules: {
      'vue/multi-word-component-names': 'off',
      'vue/no-v-html': 'off', // only used for trusted, authored SVG art
      'vue/max-attributes-per-line': 'off',
      'vue/singleline-html-element-content-newline': 'off',
      'vue/html-self-closing': 'off',
    },
  },
]
