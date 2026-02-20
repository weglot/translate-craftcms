// Cache for translations to avoid duplicate API calls
const translationCache = new Map();
// Pending requests queue with debounce
const pendingRequests = new Map();
const DEBOUNCE_DELAY = 600; // 600ms delay

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

		// Clear previous pending request for this query
		if (pendingRequests.has(queryKey)) {
			clearTimeout(pendingRequests.get(queryKey));
		}

		// Debounce: wait before processing
		const timeoutId = setTimeout(() => {
			pendingRequests.delete(queryKey);

			const callbacks = [];
			if (parsedBody.query) {
				callbacks.push(parseAlgoliaQueryValue(parsedBody.query, reverseWord => {
					parsedBody.query = reverseWord;
				}));
			}

			if (parsedBody.requests) {
				for (const algoliaRequest of parsedBody.requests) {
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
				request.body = JSON.stringify(parsedBody);
				const apiKey = weglotData.api_key.replace('wg_', '');
				const url = request.url.replace(/^https?:\/\//, '');
				request.url = 'https://proxy.weglot.com/' + apiKey + '/' + weglotData.original_language + '/' + weglotData.current_language + '/' + url;

				callback();
			});
		}, DEBOUNCE_DELAY);

		pendingRequests.set(queryKey, timeoutId);
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
