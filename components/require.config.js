var components = {
    "packages": [
        {
            "name": "cron-manager-bundle"
        },
        {
            "name": "jquery",
            "main": "jquery.js"
        },
        {
            "name": "annotations"
        },
        {
            "name": "cache"
        },
        {
            "name": "collections"
        },
        {
            "name": "common"
        },
        {
            "name": "dbal"
        },
        {
            "name": "doctrine-bundle"
        },
        {
            "name": "inflector"
        },
        {
            "name": "lexer"
        },
        {
            "name": "orm"
        },
        {
            "name": "simplepie-bundle"
        },
        {
            "name": "doctrine-extensions"
        },
        {
            "name": "sql-formatter"
        },
        {
            "name": "aop-bundle"
        },
        {
            "name": "cg"
        },
        {
            "name": "di-extra-bundle"
        },
        {
            "name": "metadata"
        },
        {
            "name": "parser-lib"
        },
        {
            "name": "security-extra-bundle"
        },
        {
            "name": "assetic"
        },
        {
            "name": "lessphp"
        },
        {
            "name": "monolog"
        },
        {
            "name": "phpoption"
        },
        {
            "name": "log"
        },
        {
            "name": "cssembed"
        },
        {
            "name": "component-installer"
        },
        {
            "name": "distribution-bundle"
        },
        {
            "name": "framework-extra-bundle"
        },
        {
            "name": "generator-bundle"
        },
        {
            "name": "simplepie"
        },
        {
            "name": "swiftmailer"
        },
        {
            "name": "assetic-bundle"
        },
        {
            "name": "monolog-bundle"
        },
        {
            "name": "swiftmailer-bundle"
        },
        {
            "name": "symfony"
        },
        {
            "name": "extensions"
        },
        {
            "name": "twig"
        },
        {
            "name": "bootstrap"
        },
        {
            "name": "framework-standard-edition"
        }
    ],
    "baseUrl": "components"
};
if (typeof require !== "undefined" && require.config) {
    require.config(components);
} else {
    var require = components;
}
if (typeof exports !== "undefined" && typeof module !== "undefined") {
    module.exports = components;
}