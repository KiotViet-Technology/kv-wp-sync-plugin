import { openDB, deleteDB } from "idb";

if (!("indexedDB" in window)) {
	console.log("This browser doesn't support IndexedDB");
}

const indexDbService = {
	db: "",
	openDB: async function(name, version, upgrade) {
		const dbPromise = await openDB(name, version, upgrade);
		this.db = dbPromise;
		return dbPromise;
	},
	get: async function(name, key) {
		return await this.db
			.transaction(name, "readonly")
			.objectStore(name)
			.get(key);
	},
	add: async function(name, data = []) {
		const tx = this.db.transaction(name, "readwrite");
		const store = tx.objectStore(name);
		let key = await store.count();
		try {
			data.forEach(value => {
				const object = {
					...value,
					key
				};
				store.put(object);
				key++;
			});
			await tx.done;
		} catch (error) {
			console.log(`error: Add ${name} to indexedDB has erros`);
			console.log(data, key, name);
		}
	},
	update: async function(name, data) {
		const tx = this.db.transaction(name, "readwrite");
		const store = tx.objectStore(name);
		store.put(data);
		await tx.done;
	},
	delete: async function(name, data) {
		const tx = this.db.transaction(name, "readwrite");
		const store = tx.objectStore(name);
		data.forEach(key => {
			store.delete(key);
		});
		await tx.done;
	},
	getAll: async function(name) {
		const data = [];
		let cursor = await this.db
			.transaction(name, "readonly")
			.objectStore(name)
			.openCursor();
		while (cursor) {
			data.push(cursor.value);
			cursor = await cursor.continue();
		}
		return data;
	},
	deleteDatabase: async function(name) {
		this.db.close();
		await deleteDB(name);
	},
	search: async function(name, index, value) {
		const data = [];
		const ranger = IDBKeyRange.only(value);
		let cursor = await this.db
			.transaction(name, "readonly")
			.objectStore(name)
			.index(index)
			.openCursor(ranger);
		while (cursor) {
			data.push(cursor.value);
			cursor = await cursor.continue();
		}
		return data;
	},
	count: async function(name) {
		return await this.db
			.transaction(name, "readonly")
			.objectStore(name)
			.count();
	},
	async paginate(name, from, to) {
		const data = [];
		let cursor = await this.db
			.transaction(name, "readonly")
			.objectStore(name)
			.openCursor();
		if (from > 0) {
			await cursor.advance(from);
		}

		while (cursor) {
			data.push(cursor.value);
			if (data.length < to - from) {
				cursor = await cursor.continue();
			} else {
				break;
			}
		}

		return data;
	},
	insertKv: async function(name, data = [], key) {
		const tx = this.db.transaction(name, "readwrite");
		const store = tx.objectStore(name);
		data.forEach(async value => {
			const item = await store.get(value[key]);
			const data = value;
			if (item) {
				data.update = 1;
			} else {
				data.update = 0;
			}
			await store.put(data);
		});

		await tx.done;
	},
	updateMapKv: async function(name, data = [], key) {
		const ranger = IDBKeyRange.only(1);
		let cursor = await this.db
			.transaction(name, "readwrite")
			.objectStore(name)
			.index("update")
			.openCursor(ranger);
		while (cursor) {
			const item = cursor.value;
			if (data.indexOf(item[key]) !== -1) {
				item.update = 0;
				cursor.update(item);
			}
			cursor = await cursor.continue();
		}
	},
	clear: async function(name) {
		const tx = this.db.transaction(name, "readwrite");
		const store = tx.objectStore(name);
		await store.clear(name);
	},
	createIndex: function(objectStore, index, uniqueValue = false) {
		if (!objectStore.indexNames.contains(index)) {
			objectStore.createIndex(index, index, { unique: uniqueValue });
		}
	},
	createObjectStore: function(db, transaction, objectStoreName, key) {
		if (!db.objectStoreNames.contains(objectStoreName)) {
			return db.createObjectStore(objectStoreName, {
				keyPath: key
			});
		} else {
			return transaction.objectStore(objectStoreName);
		}
	}
};

export default indexDbService;
export { indexDbService };
