const UI = (function () {
  function createElement(config) {
    if (!config) return;
    const elementType = config.element || "div";
    const element = document.createElement(elementType);

    Object.keys(config).forEach((property) => {
      const value = config[property];
      switch (property.toLowerCase()) {
        case "id":
        case "src":
        case "placeholder":
        case "title":
        case "accept":
          element.setAttribute(property, value);
          return;
        case "style":
          if (typeof value === "string")
            return element.setAttribute(property, value);
          for (let cssKey in value) {
            let cssValue = value[cssKey];
            cssKey = kebabize(cssKey);
            console.log("applying", cssKey, cssValue);
            element.style[cssKey] = cssValue;
          }
          console.log(element.style);
          return;
        case "classname":
          element.setAttribute("class", value);
          return;
        case "colspan":
          element.setAttribute("colspan", value);
          return;
        case "text":
          element.innerText = value;
          return;
        case "value":
          element.value = value;
          return;
        case "html":
          element.innerHTML = value;
          return;
        case "onclick":
          element.onclick = value.bind(element);
          return;
        case "onchange":
          element.onchange = value.bind(element);
          return;
        case "onkeydown":
          element.onkeydown = value.bind(element);
          return;
        case "onkeyup":
          element.onkeyup = value.bind(element);
          return;
        case "type":
          if (elementType === "input") element.setAttribute("type", value);
          return;
        case "children":
          if (value instanceof Array) {
            value.forEach((child) => {
              const childElement =
                child instanceof Element ? child : UI.createElement(child);
              if (!childElement) return;
              element.appendChild(childElement);
            });
          } else {
            const child = value;
            const childElement =
              child instanceof Element ? child : UI.createElement(child);
            if (!childElement) return;
            element.appendChild(childElement);
            element.appendChild(childElement);
          }
          return;
        case "disabled":
          if (value) element.setAttribute("disabled", true);
          return;
        case "checked":
          if (value) element.setAttribute("checked", true);
          return;
        case "indeterminate":
          element.indeterminate = value;
          return;
      }
    });
    return element;
  }

  function getElement(id) {
    return document.getElementById(id);
  }

  function getRoot() {
    return document.getElementsByTagName("body")[0];
  }

  function saveScrollPosition(elementId) {
    let scrollElement = UI.getElement(elementId);
    if (!scrollElement) return;
    UI.scrollPositions[elementId] = {
      scrollLeft: scrollElement.scrollLeft,
      scrollRight: scrollElement.scrollRight,
    };
  }

  function loadScrollPosition(elementId) {
    let scrollElement = UI.getElement(elementId);
    if (!scrollElement) return;
    if (!UI.scrollPositions[elementId]) return;
    scrollElement.scrollLeft = UI.scrollPositions[elementId].scrollLeft;
    scrollElement.scrollRight = UI.scrollPositions[elementId].scrollRight;
  }

  function kebabize(str) {
    return str
      .split("")
      .map((letter, idx) => {
        return letter.toUpperCase() === letter
          ? `${idx !== 0 ? "-" : ""}${letter.toLowerCase()}`
          : letter;
      })
      .join("");
  }

  return {
    createElement,
    getElement,
    getRoot,
    saveScrollPosition,
    loadScrollPosition,
  };
})();
