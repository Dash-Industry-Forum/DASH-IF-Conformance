function MainView() {
  const PAGE_HOME = "home";

  let _navigationBar = new NavigationBar();
  let _activePage = PAGE_HOME;
  let _rootElementId;
  let _contentElementId;

  function handleLocationChange(location) {
    if (location === _activePage) return;
    _navigationBar.setActiveLocation(location);
    _activePage = location;
    _navigationBar.renderButtons();
    renderContent();
  }
  _navigationBar.onLocationChange(handleLocationChange);

  function render(rootElement) {
    _rootElementId = rootElement = rootElement || _rootElementId;
    let navigationElementId = UI.generateElementId();
    let contentElementId = UI.generateElementId();
    let mainView = UI.createElement({
      id: _rootElementId,
      style: {
        width: "100%",
        height: "100%",
        backgroundColor: "#eee",
        color: "#444",
      },
      className: "d-flex flex-column",
      children: [
        { id: navigationElementId },
        {
          className: "container-xl flex-fill py-4 overflow-auto",
          style: {
            backgroundColor: "white",
          },
          children: { id: contentElementId },
        },
      ],
    });

    UI.replaceElement(_rootElementId, mainView);

    _navigationBar.render(navigationElementId);
    renderContent(contentElementId);
  }

  function renderContent(rootElementId) {
    _contentElementId = rootElementId = rootElementId || _contentElementId;
    console.log("render content");
    UI.clearElement(rootElementId);
    switch (_activePage) {
      case PAGE_HOME:
        let toolView = new ToolView();
        return toolView.render(rootElementId);
    }
  }

  let instance = {
    render,
  };
  return instance;
}
