The following is a planning/brainstorming page. It describes the ideal state of the WebLite interface once implemented.

WebPro is an advanced interface that relies on Javascript for as many tasks as possible. It is much lighter on the server, but much heavier on the client. WebLite will largely reverse this. It will offload as many tasks as possible to the server, instead simply sending text to the client to display. Below are several distinctions that will exist between these two official interfaces:

1. **Browser Support** - WebPro will only work with Internet Explorer 9 and up (as well as recent versions of all other browsers). WebLite, however, will work with Internet Explorer 6, Opera 9, Safari 4, and Firefox 4 (as well as the most recent version of Chrome), as well as the vast majority of smart phone and tablet browsers.

2. **Phrases on the Client Side** - Phrases are implemented largely with Javascript in WebPro. In WebLite, a PHP phrase system will be implemented, however it will only be modifiable using a text editor, thus avoiding unnecessary MySQL/server strain.

3. **Use of jQuery** - jQuery will still be used with WebLite, however jQueryUI will be dropped in favour of a custom widgeting system that will run on most clients. In FreezeMessenger 4.0, if possible, only jQuery 2.0 will be used in WebPro, as well, while WebLite will also be able to use jQuery 1.9.

4. **Dialogue Use** -  Dialogues are used for most actions in WebPro. Instead, separate PHP pages will be used with WebLite. This can currently be seen implemented in the register script, which will also be modified to use the custom widget set once WebLite is completed.