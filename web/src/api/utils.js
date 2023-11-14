// 解析url
export const parseUrl = (url, query = {}) => {
  const paths = url.split("/");
  let newUrl = "";

  paths.map(item => {
    if (/^{[^({|})]*}$/.test(item)) {
      item = item.replace("{", "").replace("}", "");

      if (query[item] === undefined) {
        throw new Error(`query ${item} is undefined`);
      }

      item = query[item];
    }

    newUrl = `${newUrl + item}/`;
    return item;
  });

  return newUrl.substring(0, newUrl.length - 1);
};

// 添加前缀
export const addPrefix = (url, prefix = "/api") => prefix + url;
