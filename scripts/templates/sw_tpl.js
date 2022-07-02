const cache_domain = "<?= $domain ?>";
const cache_info = [{
  name: "<?= $domain ?>-redirect-v<?= $date ?>01",
  list: [
    "/<?= $domain ?>/redirect.html",
    "/<?= $domain ?>/icon.png",
  ]
}];

let cache_tags = [];
const cache_info_length = cache_info.length;
for (let i = 0; i < cache_info_length; ++i) {
    cache_tags.push(cache_info[i].name);
}

self.addEventListener("install", e => {
  const preCache = async (cache_name, url_to_cache) => {
    const cache = await caches.open(cache_name);
    return cache.addAll(url_to_cache);
  };

  e.waitUntil(preCache(cache_info[0].name, cache_info[0].list));
  e.waitUntil(self.skipWaiting());
});

self.addEventListener("activate", e => {
  const cacheClear = async () => caches.keys().then(keys => keys
    .filter(key => key.split("-")[0] === cache_domain)
    .filter(key => cache_tags.indexOf(key) < 0)
    .map(key => caches.delete(key))
  );

  e.waitUntil(cacheClear());
  e.waitUntil(self.clients.claim());
});

self.addEventListener("fetch", e => {
  const onlineRequest = async (request, cache_name) => {
    const cache = await caches.open(cache_name);
    return cache.match(request, {ignoreSearch: true}).then(response => {
      if (response) {
        return response;
      }
      return fetch(request);
    });
  };
  const offlineRequest = async () => caches.match("/<?= $domain ?>/redirect.html")
    .then(response => response);

  if (self.navigator.onLine) {
    e.respondWith(onlineRequest(e.request, cache_info[0].name));
  } else {
    e.respondWith(offlineRequest());
  }
});
