function MainView() {
  const PAGE_HOME = "home";
  const PAGE_ABOUT = "about";
  const PAGE_FAQ = "faq";

  const locations = [
    { id: "home", text: "Validator", icon: "fa-solid fa-gears", getView: () => new ToolView() },
    { id: "about", text: "About", icon: "fa-solid fa-info-circle", getView: () => new AboutView() },
    { id: "faq", text: "FAQ", icon: "fa-solid fa-question-circle", getView: () => new FaqView() },
  ];

  let _navigationBar = new NavigationBar();
  _navigationBar.setLocations(locations);
  _navigationBar.setActiveLocation(locations[0].id);
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
          className: "flex-fill overflow-scroll",
          children: {
            className: "container-xl py-4",
            style: {
              backgroundColor: "white",
              minHeight: "100%",
            },
            children: { id: contentElementId },
          },
        },
      ],
    });

    UI.replaceElement(_rootElementId, mainView);

    _navigationBar.render(navigationElementId);
    renderContent(contentElementId);
  }

  function renderContent(rootElementId) {
    _contentElementId = rootElementId = rootElementId || _contentElementId;
    UI.clearElement(rootElementId);
    let location = locations.find(location => location.id === _activePage)
    let view = location.getView();
    view.render(rootElementId);
  }

  let instance = {
    render,
  };
  return instance;
}
