YUI.add('netgen-tags-field-edit-view', function (Y) {
    "use strict";

    Y.namespace('Netgen.Tags.View.Field');

    Y.Netgen.Tags.View.Field.TagsEditView = Y.Base.create('NetgenTagsFieldEditView', Y.eZ.FieldEditView, [], {
        initializer: function () {
            this.after('activeChange', function (e) {
                if (this.get('active')) {
                    this.initTagsTranslations();
                    jQuery('#eztags' + this.get('fieldDefinition').id).EzTags();
                    ngTagsInit();
                }
            });
        },

        initTagsTranslations: function () {
            jQuery.EzTags.Base.defaults.translations = {
                selectedTags: 'Selected tags',
                loading: 'Loading...',
                noSelectedTags: 'There are no selected tags',
                suggestedTags: 'Suggested tags',
                noSuggestedTags: 'There are no tags to suggest',
                addNew: 'Add new',
                clickAddThisTag: 'Click to add this tag',
                removeTag: 'Remove tag',
                translateTag: 'Translate tag',
                existingTranslations: 'Existing translations',
                noExistingTranslations: 'No existing translations',
                addTranslation: 'Add translation',
                cancel: 'Cancel',
                ok: 'OK',
            };
        },

        _variables: function () {
            var fieldSettings = this.get('fieldDefinition').fieldSettings;

            return {
                editView: fieldSettings.editView !== '' ?
                    fieldSettings.editView :
                    'Default',
                hideRootTag: fieldSettings.hideRootTag,
                maxTags: fieldSettings.maxTags,
                subTreeLimit: fieldSettings.subTreeLimit,

                languageCode: this.get('languageCode'),

                tagNames: this.get('tagNames'),
                tagPids: this.get('tagPids'),
                tagIds: this.get('tagIds'),
                tagLocales: this.get('tagLocales')
            };
        },

        _getFieldValue: function () {
            var container = Y.one('#eztags' + this.get('fieldDefinition').id);

            var tagIds = container.one('.tagids').get('value').split('|#');
            var tagPids = container.one('.tagpids').get('value').split('|#');
            var tagNames = container.one('.tagnames').get('value').split('|#');
            var tagLocales = container.one('.taglocales').get('value').split('|#');

            var value = [];

            for (var i in tagIds) {
                if (tagPids[i] === undefined || tagNames[i] === undefined || tagLocales[i] === undefined) {
                    break;
                }

                if (tagIds[i] === '0') {
                    var keywords = {};
                    keywords[tagLocales[i]] = tagNames[i];

                    value.push({
                        parent_id: tagPids[i],
                        keywords: keywords,
                        main_language_code: tagLocales[i]
                    });
                } else {
                    value.push({id: tagIds[i]});
                }
            }

            return value;
        },
    },{
        ATTRS: {
            tagNames: {
                valueFn: function () {
                    var field = this.get('field');
                    var languageCode = this.get('languageCode');
                    return field.fieldValue.map(function(elem) {
                        if (elem.keywords.hasOwnProperty(languageCode)) {
                            return elem.keywords[languageCode];
                        }

                        return elem.keywords[elem.main_language_code];
                    }).join("|#");
                }
            },

            tagPids: {
                valueFn: function () {
                    return this.get('field').fieldValue.map(function(elem) {
                        return elem.parent_id;
                    }).join("|#");
                }
            },

            tagIds: {
                valueFn: function () {
                    return this.get('field').fieldValue.map(function(elem) {
                        return elem.id;
                    }).join("|#");
                }
            },

            tagLocales: {
                valueFn: function () {
                    var field = this.get('field');
                    var languageCode = this.get('languageCode');
                    return field.fieldValue.map(function(elem) {
                        if (elem.keywords.hasOwnProperty(languageCode)) {
                            return languageCode;
                        }

                        return elem.main_language_code;
                    }).join("|#");
                }
            }
        }
    });

    Y.eZ.FieldEditView.registerFieldEditView('eztags', Y.Netgen.Tags.View.Field.TagsEditView);
});
