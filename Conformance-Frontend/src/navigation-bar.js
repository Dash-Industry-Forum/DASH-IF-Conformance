function NavigationBar() {
  const EVENT_LOCATION_CHANGE = "location_change";

  let locations = [];

  let _rootElementId;
  let _buttonsElementId;
  let eventHandler = new EventHandler();

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

  function setLocations(newLocations) {
    locations = newLocations;
  }

  function render(rootElementId) {
    _rootElementId = rootElementId = rootElementId || _rootElementId;
    let buttonsElementId = UI.generateElementId();
    let navigationBar = UI.createElement({
      id: _rootElementId,
      style: {
        backgroundColor: "#007BFF",
        width: "100%",
        paddingTop: "0.5em",
        paddingBottom: "0.5em",
        color: "white",
      },
      className: "main-navigation",
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
              id: buttonsElementId,
            },
          ],
        },
      ],
    });
    UI.replaceElement(_rootElementId, navigationBar);
    renderButtons(buttonsElementId);
  }

  function renderButtons(elementId) {
    _buttonsElementId = elementId = elementId || _buttonsElementId;
    let buttons = UI.createElement({
      id: _buttonsElementId,
      element: "ul",
      className: "nav nav-pills",
      children: locations.map((location) => ({
        element: "li",
        className: "nav-item",
        children: [
          {
            element: "a",
            className:
              "nav-link" + (activeLocation === location.id ? " active" : ""),
            href: "#",
            onclick: (event) => {
              handleLocationChange(location.id);
              event.preventDefault();
            },
            children: [
              location.icon
                ? { element: "i", className: location.icon + " me-2" }
                : null,
              { element: "span", text: location.text },
            ],
          },
        ],
      })),
    });
    UI.replaceElement(elementId, buttons);
  }

  let instance = {
    render,
    renderButtons,
    onLocationChange,
    offLocationChange,
    setActiveLocation,
    setLocations,
  };
  return instance;
}
