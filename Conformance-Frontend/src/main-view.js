function MainView() {
  const PAGE_HOME = "home";

  let navigationBar = new NavigationBar();
  let activePage = PAGE_HOME;

  function render() {
    return UI.createElement({
      style: {
        width: "100%",
        height: "100%",
        backgroundColor: "#eee",
        color: "#444"
      },
      className: "d-flex flex-column",
      children: [
        navigationBar.render(),
        {
          className: "container-xl flex-fill pt-4",
          style: {
            backgroundColor: "white",
          },
          children: renderContent()
        },
      ],
    });
  }

  function renderContent() {
    switch(activePage) {
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
