// @ts-check

import js from "@eslint/js";
import pluginVue from "eslint-plugin-vue";
import globals from "globals";

/**
 * @type {import("eslint").Linter.Config[]}
 */
export default [
    js.configs.recommended,
    ...pluginVue.configs["flat/strongly-recommended"],
    {
        files: ["**/*.vue", "**/*.js"],
        languageOptions: {
            sourceType: "module",
            globals: {
                ...globals.browser,
            },
        },
    },
];
