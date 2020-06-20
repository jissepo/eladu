const path = require("path");

module.exports = {
  mode: "development",
  entry: "./assets/js/main.js",
  output: {
    filename: "build.js",
    path: path.resolve(__dirname, "assets/js"),
  },
  resolve: { alias: { vue: "vue/dist/vue.esm.js" } },
  module: {
    rules: [
      {
        test: /\.css$/i,
        use: ["style-loader", "css-loader"],
      },
    ],
  },
};
