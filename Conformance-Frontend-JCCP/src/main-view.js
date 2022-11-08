function MainView() {
  const PAGE_HOME = "home";
  const PAGE_ABOUT = "about";
  const PAGE_FAQ = "faq";

  const locations = [
    { id: "home", text: "Validator", icon: "fa-solid fa-gears", view: ToolView },
    { id: "about", text: "About", icon: "fa-solid fa-info-circle", view: AboutView },
    { id: "faq", text: "FAQ", icon: "fa-solid fa-question-circle", view: FaqView },
  ];

  let _navigationBar = new NavigationBar();
  _navigationBar.setLocations(locations);
  let _activePage = getLocation();
  _navigationBar.setActiveLocation(_activePage);
  let _rootElementId;
  let _contentElementId;

  function getLocation() {
    let hash = location.hash;
    console.log(hash);
    let page = hash.substring(1, hash.length);
    console.log(page);
    if (page) return page;
    location.hash = PAGE_HOME;
    return PAGE_HOME;
  }

  function handleLocationChange(locationId) {
    if (locationId === _activePage) return;
    _navigationBar.setActiveLocation(locationId);
    _activePage = locationId;
    location.hash = locationId;
    console.log(location.hash);
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
    let view = new location.view();
    view.render(rootElementId);
  }

  let instance = {
    render,
  };
  return instance;
}
