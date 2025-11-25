import js from "@eslint/js";

export default [
	js.configs.recommended,
	{
		files: ["resources/js/**/*.js", "resources/views/**/*.blade.php"],
		languageOptions: {
			ecmaVersion: 2021,
			sourceType: "module",
			globals: {
				window: "readonly",
				document: "readonly",
				console: "readonly",
				$: "readonly",
				jQuery: "readonly",
				axios: "readonly",
				Swal: "readonly",
				toastr: "readonly",
				moment: "readonly",
				DataTable: "readonly",
				fetch: "readonly",
			},
		},
		rules: {
			"brace-style": ["error", "allman", { allowSingleLine: false }],
			indent: ["error", "tab", { SwitchCase: 1 }],
			semi: ["error", "always"],
			quotes: ["error", "single", { avoidEscape: true, allowTemplateLiterals: true }],
			"no-unused-vars": ["warn", { argsIgnorePattern: "^_", varsIgnorePattern: "^_" }],
			"no-undef": "warn",
			"no-console": "off",
			"no-mixed-spaces-and-tabs": ["error", "smart-tabs"],
			"comma-dangle": ["error", "never"],
			"space-before-function-paren": ["error", "never"],
			"keyword-spacing": ["error", { before: true, after: true }],
			"space-infix-ops": "error",
			"arrow-parens": ["error", "always"],
			"arrow-spacing": ["error", { before: true, after: true }],
			"object-curly-newline": ["error", {
				multiline: true,
				consistent: true
			}],
			"function-paren-newline": ["error", "consistent"],
		},
	},
];
