<template>

    <div id="sim-mde">
        <div class="field">
            <div class="control">
                <textarea id="editor"></textarea>
            </div>
        </div>
        <div class="bottom-toolbar fullscreen">

            <div class="bottom-tool">

                <a @click="trigger('toggleBold')" title="Bold (Cmd-B)" tabindex="-1" class="fa fa-bold"></a>
                <a @click="trigger('toggleItalic')" title="Italic (Cmd-I)" tabindex="-1" class="fa fa-italic"></a>
                <a @click="trigger('toggleStrikethrough')" title="Strikethrough" tabindex="-1"
                   class="fa fa-strikethrough"></a>
                <a @click="trigger('toggleHeadingSmaller')" title="Heading (Cmd-H)" tabindex="-1"
                   class="fa fa-header"></a>
                <a @click="trigger('toggleCodeBlock')" title="Code (Cmd-⌥-C)" tabindex="-1" class="fa fa-code"></a>
                <a @click="trigger('toggleBlockquote')" title="Quote (Cmd-')" tabindex="-1"
                   class="fa fa-quote-left"></a>
                <a @click="trigger('toggleUnorderedList')" title="Generic List (Cmd-L)" tabindex="-1"
                   class="fa fa-list-ul"></a>
                <a @click="trigger('toggleOrderedList')" title="Numbered List (Cmd-⌥-L)" tabindex="-1"
                   class="fa fa-list-ol"></a>
                <a @click="trigger('drawLink')" title="Create Link (Cmd-K)" tabindex="-1" class="fa fa-link"></a>
                <a @click="selectImage" title="Insert Image" tabindex="-1" class="fa fa-picture-o"></a>
                <a @click="trigger('drawTable')" title="Insert Table" tabindex="-1" class="fa fa-table"></a>
                <a @click="trigger('drawHorizontalRule')" title="Insert Horizontal Line" tabindex="-1"
                   class="fa fa-minus"></a>
                <a @click="trigger('togglePreview')" title="Toggle Preview (Cmd-P)" tabindex="-1"
                   class="fa fa-eye no-disable"></a>
                <a @click="trigger('toggleSideBySide')" title="Toggle Side by Side (F9)" tabindex="-1"
                   class="fa fa-columns no-disable no-mobile"></a>
                <a href="https://simplemde.com/markdown-guide" target="_blank" title="Markdown Guide" tabindex="-1"
                   class="fa fa-question-circle"></a>

            </div>

            <div class="right-box p-r-10">
                <slot name="bottom-right"></slot>
            </div>

        </div>

        <div class="top-toolbar fullscreen">

            <input v-model="title" type="text" class="title-input" :placeholder="placeHolderTitle">
            <div class="right-box p-r-20">

                <slot></slot>

            </div>

        </div>

        <input type="file" id="btn_file" style="display:none">

    </div>

</template>


<script>
    import {default as SimpleMDE} from 'simplemde/dist/simplemde.min.js'
    import MdeOption from './modules/MdeConfig.js'
    import * as qiniu from 'qiniu-js'

    export default {
        props: [
            'titleText',
            'bodyText',
            'placeHolderTitle',
            'placeHolderBody'
        ],
        data() {
            return {
                simplemde: '',
                title: '',
                shouldEmit:false,
                token: '', // 七牛token
            }
        },
        mounted() {

            let placeHolder = this.placeHolderBody

            this.simplemde = new SimpleMDE({
                toolbar: MdeOption.getToolBarConfig(),
                element: document.getElementById("editor"),
                placeholder: placeHolder,
                autoDownloadFontAwesome: true,
                spellChecker: false
            })


            this.simplemde.toggleFullScreen()
            this.simplemde.toggleSideBySide()

            this.simplemde.codemirror.on("change", function () {
                if (!this.shouldEmit){
                    return
                }
                this.$emit('edit-change', {
                    mde: this.simplemde,
                    title: this.title
                });
            }.bind(this));

            // 粘贴图片的触发函数
            this.simplemde.codemirror.on('paste', (editor, e) => {
                if (!(e.clipboardData && e.clipboardData.items)) {
                    // 弹窗说明，此浏览器不支持此操作
                    return
                }
                try {
                    let dataList = e.clipboardData.items;
                    if (dataList[0].kind === 'file' && dataList[0].getAsFile().type.indexOf('image') !== -1) {
                        this.uploadImage(dataList[0].getAsFile())
                    }
                } catch (e) {
                    console.log(e);
                    // 弹窗说明，只能粘贴图片
                }
            })
        },

        created() {
            window.onresize = this.adjustPreviewWidth;
            axios.get('/api/v1/drafts/getToken').then(({data})=>{
                if (data.code == 200) {
                    this.token = data.data.token;
                } else {
                    alert('getToken error');
                }
            })
        },
        watch: {
            titleText: function (value) {
                this.shouldEmit = false
                this.title = value

            },
            bodyText: function (value) {
                this.shouldEmit = false
                this.simplemde.value(value);

            },
            title:function (value) {
                if (this.shouldEmit === false){
                    this.shouldEmit = true
                    return
                }
                this.$emit('edit-change', {
                    mde: this.simplemde,
                    title: value
                });
            }

        },
        methods: {
            trigger(action) {

                let functionName = 'this.simplemde.' + action + '()';

                eval(functionName)

                if (action === 'togglePreview' || action === 'toggleSideBySide') {
                    console.log(action)
                    setTimeout(this.adjustPreviewWidth, 100)
                }
            },
            adjustPreviewWidth() {

                let simMde = document.getElementById('sim-mde');

                let cmCode = simMde.getElementsByClassName('CodeMirror-code')[0];
                let leftDistance = (window.innerWidth - cmCode.offsetWidth) * 0.5;

                let isSideBySide = this.simplemde.isSideBySideActive();

                this.setInputPosition(isSideBySide, leftDistance)

                this.setPreviewPosition(cmCode.offsetWidth, leftDistance)

            },
            setInputPosition(isSideActive, leftDistance) {
                let simMde = document.getElementById('sim-mde');
                let inputField = simMde.getElementsByClassName('title-input')[0]

                if (isSideActive) {

                    inputField.style.paddingLeft = 20 + 'px';
                    inputField.style.marginLeft = 0;
                    return;
                }
                inputField.style.paddingLeft = 0;

                inputField.style.marginLeft = leftDistance + 'px';
            },
            setPreviewPosition(width, paddingLeft) {

                let simMde = document.getElementById('sim-mde');
                let previewDom = simMde.getElementsByClassName('editor-preview-active')[0]
                if (!previewDom) {
                    return
                }
                console.log(previewDom)
                previewDom.style.width = (width + 20) + 'px';
                previewDom.style.marginLeft = (paddingLeft - 10) + 'px';
            },
            selectImage() {
                var fileBtn = document.getElementById("btn_file");
                fileBtn.onchange = this.uploadImage;
                fileBtn.click();
            },
            uploadImage(file) {
                if (!(file instanceof File)) {
                    var fileBtn = document.getElementById("btn_file");
                    file = fileBtn.files[0];
                }
                var formData = new FormData();
                formData.append("file", file);
                var domain = 'http://image.hbxzlgs.com/';
                var key = (new Date()).getTime() + file['name'];
                var putExtra = {
                    fname: '',
                    params: {'x:name': key},
                    mimeType: null,
                };
                var config = {
                    useCdnDomain: true,
                    disableStatisticsReport: false,
                    retryCount: 6,
                    region: qiniu.region.z0,
                };
                var next = function(response) {
                    console.log(response);
                };
                var error = function(error) {
                    console.log(error);
                };
                var that = this;
                var complete = function(res) {
                    var pos = that.simplemde.codemirror.getCursor();
                    that.simplemde.codemirror.setSelection(pos, pos);
                    that.simplemde.codemirror.replaceSelection('![](' + domain + res.key + ')');
                };
                var subObject = {
                    next: next,
                    error: error,
                    complete: complete
                };
                var observable = qiniu.upload(file, key, this.token, putExtra, config);
                observable.subscribe(subObject)
            }
        }
    }


</script>

<style lang="scss">
    $editor-bottom-height: 50px;
    $editor-top-height: 60px;

    #sim-mde {

        .is-45x45 {
            width: 45px;
            height: 45px;

        }

        .title-input {
            margin: 0;
            padding: 20px;
            font-size: 1.8rem;
            font-weight: 600;
            color: #555;
            border: none;
            outline: none;
            -webkit-box-flex: 1;
            -ms-flex: 1 1 auto;
            flex: 1 1 auto;
            height: 100%;
        }

        .CodeMirror-fullscreen {
            bottom: $editor-bottom-height;
            top: $editor-top-height;
        }
        .CodeMirror {
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;

        }

        @media screen and (min-width: 992px) {

            .CodeMirror-lines {
                margin: 0 auto;
                max-width: 1000px;

            }
        }
        .editor-preview-side {
            bottom: $editor-bottom-height;
            top: $editor-top-height;
        }
        .editor-toolbar.fullscreen {
            display: none;
        }

        .bottom-toolbar.fullscreen {

            width: 100%;
            height: 50px;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            padding-top: 10px;
            padding-bottom: 10px;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            background: #fff;
            border: 0;
            position: fixed;
            bottom: 0;
            left: 0;
            opacity: 1;
            z-index: 50;
            display: flex;

        }

        .top-toolbar.fullscreen {

            width: 100%;
            height: $editor-top-height;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            padding-top: 10px;
            padding-bottom: 10px;
            -webkit-box-sizing: border-box;
            box-sizing: border-box;
            background: #fff;
            border: 0;
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            opacity: 1;
            z-index: 50;
            display: flex;

        }

        .bottom-tool {
            flex: 1;
            padding: 0 20px;
            font-size: 20px;
            a {
                color: #555;
                padding: 0 5px;
            }

        }

        .right-box {

            display: flex;
            -webkit-box-pack: end;
            -ms-flex-pack: end;
            justify-content: flex-end;
            align-items: center;
        }

        .left-box {

            display: -webkit-box;
            display: -ms-flexbox;
            display: flex;
            -webkit-box-align: center;
            -ms-flex-align: center;
            align-items: center;
        }
    }

</style>