// Cache for translations to avoid duplicate API calls
const translationCache = new Map();
// Pending requests queue
const pendingRequests = new Map();
let debounceTimer = null;
const DEBOUNCE_DELAY = 800; // 800ms delay

function parseAlgoliaQueryValue(queryValue, replaceFn) {
	return () => {
		// Check cache first
		if (translationCache.has(queryValue)) {
			replaceFn(translationCache.get(queryValue));
			return Promise.resolve();
		}

		return reverseTranslate(weglotData.api_key, weglotData.current_language, weglotData.original_language, 'https://' + window.location.hostname, queryValue, 1)
			.then(data => {
				if (data && data.to_words[0] !== undefined) {
					translationCache.set(queryValue, data.to_words[0]);
					replaceFn(data.to_words[0]);
				}
			})
	};
}

function parseAlgoliaRequest(algoliaRequest) {
	try {
		const params = new URLSearchParams(algoliaRequest?.params);
		if (params.has("query")) {
			return parseAlgoliaQueryValue(params.get("query"), reverseWord => {
				params.set("query", reverseWord);
				algoliaRequest.params = params.toString();
			});
		}
	} catch (e) {
		console.error(e);
	}
}

document.addEventListener('DOMContentLoaded', function () {
	xhook.before(function (request, callback) {
		if (!request.url || !request.url.includes('x-algolia-agent') || weglotData.original_language === weglotData.current_language) {
			return callback();
		}

		let parsedBody;
		try {
			parsedBody = JSON.parse(request.body);
		} catch (error) {
			console.error('Failed to parse request.body:', error);
			return callback();
		}

		// Extract query for debouncing
		let queryKey = '';
		if (parsedBody.query) {
			queryKey = parsedBody.query;
		} else if (parsedBody.requests && parsedBody.requests[0]?.params) {
			const params = new URLSearchParams(parsedBody.requests[0].params);
			queryKey = params.get("query") || '';
		}

		// Clear previous timer
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}

		// Store this request
		const requestId = Date.now() + Math.random();
		pendingRequests.set(requestId, { request, callback, parsedBody, queryKey });

		// Set new timer
		debounceTimer = setTimeout(() => {
			// Process only the last request
			const lastEntry = Array.from(pendingRequests.entries()).pop();
			if (!lastEntry) return;

			const [lastId, { request: lastRequest, callback: lastCallback, parsedBody: lastParsedBody }] = lastEntry;

			// Clear all pending except the last one
			pendingRequests.clear();

			// Process the last request
			const callbacks = [];
			if (lastParsedBody.query) {
				callbacks.push(parseAlgoliaQueryValue(lastParsedBody.query, reverseWord => {
					lastParsedBody.query = reverseWord;
				}));
			}

			if (lastParsedBody.requests) {
				for (const algoliaRequest of lastParsedBody.requests) {
					callbacks.push(parseAlgoliaRequest(algoliaRequest));
				}
			}

			let promise = Promise.resolve();
			for (const cb of callbacks) {
				promise = promise.then(() => {
					if (cb) return cb();
				});
			}

			promise.then(() => {
				lastRequest.body = JSON.stringify(lastParsedBody);
				const apiKey = weglotData.api_key.replace('wg_', '');
				const url = lastRequest.url.replace(/^https?:\/\//, '');
				lastRequest.url = 'https://proxy.weglot.com/' + apiKey + '/' + weglotData.original_language + '/' + weglotData.current_language + '/' + url;

				lastCallback();
			});
		}, DEBOUNCE_DELAY);

		// Abort all requests except the one that will be processed
		if (requestId !== Array.from(pendingRequests.keys()).pop()) {
			return; // Don't call callback, effectively cancelling this request
		}
	});

	xhook.after(function (request, response) {
		if (request.url && request.url.includes('x-algolia-agent') && weglotData.original_language !== weglotData.current_language) {
			let apiKey = weglotData.api_key.replace('wg_', '');
			let url = request.url;
			url = url.replace(/^https?:\/\//, '');
			const proxifyUrl = 'https://proxy.weglot.com/' + apiKey + '/' + weglotData.original_language + '/' + weglotData.current_language + '/' + url;
		}
	});
});

function reverseTranslate(apiKey, l_from, l_to, request_url, word, t) {
	const requestBody = {
		"l_from": l_from,
		"l_to": l_to,
		"request_url": request_url,
		"words": [
			{"w": word, "t": t}
		]
	};

	const apiUrl = `${weglotData.api_url}/translate?api_key=${apiKey}`;

	return fetch(apiUrl, {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json'
		},
		body: JSON.stringify(requestBody)
	})
		.then(response => {
			if (!response.ok) {
				throw new Error('Network response was not ok');
			}
			return response.json();
		})
		.catch(error => {
			console.error('There was a problem with your fetch operation:', error);
		});
}
