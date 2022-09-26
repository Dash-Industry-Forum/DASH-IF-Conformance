function MainView() {
  const PAGE_HOME = "home";

  let _navigationBar = new NavigationBar();
  let _activePage = PAGE_HOME;
  let _rootElement;

  function handleLocationChange(location) {
    _navigationBar.setActiveLocation(location);
    _activePage = location;
    render();
  }
  _navigationBar.onLocationChange(handleLocationChange);

  function render(rootElement) {
    _rootElement = rootElement = rootElement || _rootElement;
    rootElement.innerHTML = "";
    let mainView = UI.createElement({
      style: {
        width: "100%",
        height: "100%",
        backgroundColor: "#eee",
        color: "#444"
      },
      className: "d-flex flex-column",
    });
    rootElement.appendChild(mainView);
    let navigationRoot = UI.createElement({});
    _navigationBar.render(navigationRoot);
    mainView.appendChild(navigationRoot);
    let content = UI.createElement(
      {
        className: "container-xl flex-fill pt-4",
        style: {
          backgroundColor: "white",
        },
        children: renderContent()
      }
    )
    mainView.appendChild(content);
  }

  function renderContent() {
    switch (_activePage) {
      case PAGE_HOME:
        let toolView = new ToolView();
        return toolView.render();
    }
  }

  let instance = {
    render,
  };
  return instance;
}
