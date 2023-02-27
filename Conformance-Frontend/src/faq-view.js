function FaqView() {
  let instance = {};

  let _state = {
    markdown: null,
  };

  let _rootElementId;

  async function loadMarkdown() {
    _state.markdown = await Net.sendRequest({
      method: "GET",
      uri: "./static/faq-page.md",
    });
    render();
  }

  function generateContent() {
    if (!_state.markdown) return;
    return Tools.markdownToHtml(_state.markdown);
  }

  function render(rootElementId) {
    _rootElementId = rootElementId = rootElementId || _rootElementId;

    let aboutView = UI.createElement({
      id: rootElementId,
    });

    if (!_state.markdown) {
      loadMarkdown();
      return;
    }

    let content = generateContent();
    aboutView.appendChild(content);

    UI.replaceElement(_rootElementId, aboutView);
  }

  instance = {
    render,
  };

  return instance;
}
