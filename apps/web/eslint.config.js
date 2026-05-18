import js from '@eslint/js';
import prettier from 'eslint-config-prettier';
import importPlugin from 'eslint-plugin-import';
import jsxA11y from 'eslint-plugin-jsx-a11y';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
  { ignores: ['dist', 'coverage', 'node_modules'] },
  {
    files: ['**/*.{ts,tsx}'],
    extends: [
      js.configs.recommended,
      ...tseslint.configs.recommended,
      reactHooks.configs.flat['recommended-latest'],
      reactRefresh.configs.vite,
      jsxA11y.flatConfigs.recommended,
      importPlugin.flatConfigs.recommended,
      importPlugin.flatConfigs.typescript,
      // ADR-0012: eslint-config-prettier last, so Prettier owns formatting.
      prettier,
    ],
    languageOptions: {
      ecmaVersion: 2023,
      globals: globals.browser,
    },
    settings: {
      'import/resolver': {
        typescript: {
          project: ['tsconfig.app.json', 'tsconfig.node.json'],
          noWarnOnMultipleProjects: true,
        },
      },
    },
    rules: {
      'react-refresh/only-export-components': ['error', { allowConstantExport: true }],
      'import/order': [
        'error',
        {
          groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
          'newlines-between': 'always',
          alphabetize: { order: 'asc', caseInsensitive: true },
        },
      ],
    },
  },
  {
    // Playwright fixtures destructure nothing by convention: async ({}, use).
    files: ['e2e/**'],
    rules: {
      'no-empty-pattern': 'off',
    },
  },
  {
    // shadcn/ui primitives receive their control association (htmlFor) at usage
    // sites and export cva() variant objects alongside their component.
    files: ['src/shared/ui/**'],
    rules: {
      'jsx-a11y/label-has-associated-control': 'off',
      'react-refresh/only-export-components': 'off',
    },
  },
);
