function NavigationBar() {
  function render() {
    return UI.createElement({
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
              className: "px-3",
              children: {
                text: "Home",
              },
            },
            {
              className: "px-3",
              children: {
                text: "About",
              },
            },
            {
              className: "px-3",
              children: {
                text: "How to use",
              },
            },
            {
              className: "px-3",
              children: {
                text: "FAQ",
              },
            },
          ],
        },
      ],
    });
  }

  let instance = {
    render,
  };
  return instance;
}
