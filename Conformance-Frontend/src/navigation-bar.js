function NavigationBar() {
  const EVENT_LOCATION_CHANGE = "location_change";

  const locations = [
    { id: "home", text: "Home" },
    { id: "about", text: "About" },
    { id: "howto", text: "How to use" },
    { id: "faq", text: "FAQ" }
  ]

  let _rootElement;
  let eventHandler = new EventHandler();
  let activeLocation = locations[0].id;

  function onLocationChange(callback) {
    eventHandler.on(EVENT_LOCATION_CHANGE, callback);
  }

  function offLocationChange(callback) {
    eventHandler.off(EVENT_LOCATION_CHANGE, callback);
  }

  function handleLocationChange(location) {
    eventHandler.dispatchEvent(EVENT_LOCATION_CHANGE, location);
  }

  function setActiveLocation(location) {
    activeLocation = location;
  }

  function render(rootElement) {
    _rootElement = rootElement = rootElement || _rootElement;
    let navigationBar = UI.createElement({
      style: {
        backgroundColor: "#007BFF",
        width: "100%",
        paddingTop: "0.5em",
        paddingBottom: "0.5em",
        color: "white",
      },
      children: [
        {
          className: "container-xl d-flex flex-row align-items-center",
          children: [
            {
              style: {
                backgroundColor: "white",
                width: "fit-content",
                height: "fit-content",
                borderRadius: "0.3em",
              },
              children: [
                {
                  element: "img",
                  src: "./res/Dash2.png",
                  style: {
                    height: "3em",
                  },
                },
              ],
            },
            {
              element: "span",
              className: "fw-semibold fs-4 ms-2 me-3",
              text: "Conformance Tool",
            },
            {
              element: "ul",
              className: "nav nav-pills",
              children: locations.map(location => ({
                element: "li",
                className: "nav-item",
                children: {
                  element: "a",
                  className: "nav-link" + (activeLocation === location.id ? " active" : ""),
                  text: location.text,
                  href: "#",
                  onclick: () => handleLocationChange(location.id)
                }
              })),
            },
          ],
        },
      ],
    });
    rootElement.appendChild(navigationBar);
  }

  let instance = {
    render,
    onLocationChange,
    offLocationChange,
    setActiveLocation
  };
  return instance;
}
