const fs = require('fs').promises;

const baseObj = {
    "subject": "",
    "places": "",
    "place_of_death": "",
    "place_of_birth": "",
    "place_of_burial": "",
    "notes": "",
    "patronage": "",
    "main_shrine": ""
}

const readSourceData = async () => {
    const srcObjStr = await fs.readFile(`data/LITURGY__anniversaries.json`, 'utf8');
    const srcObj = JSON.parse(srcObjStr);
    if (srcObj.hasOwnProperty('anniversary_events')) {
        console.log(`retrieved srcObj.anniversary_events: counted ${srcObj.anniversary_events.length} events`);
    }
    ['de', 'en', 'es', 'fr', 'it', 'la', 'nl', 'pt', 'sk'].forEach(lang => {
        checkObjectKeys(srcObj.anniversary_events, lang);
    });
    checkModelKeys(srcObj.anniversary_events);
}

const readLangData = async (lang = '') => {
    return await fs.readFile(`data/i18n/${lang}.json`, 'utf8');
}

const checkObjectKeys = async (anniversaryEvents, lang = '') => {
    let count = 0;
    let errorCount = 0;
    let fixCount = 0;
    const langObjStr = await readLangData(lang);
    const langObj = JSON.parse(langObjStr);

    anniversaryEvents.forEach(event => {
        const key = `${event.event_key}_${event.event_idx}`;
        if(false === langObj.hasOwnProperty(key)) {
            console.log(`lang ${lang} is missing key ${key}`);
            errorCount++;
            langObj[key] = baseObj;
            fixCount++;
        }
        count++;
    });
    const langKeyCount = Object.keys(langObj).length;
    console.log(`${count} keys were verified, ${errorCount} errors found; ${lang} has ${langKeyCount} keys`);
    if (fixCount > 0) {
        console.log(`Fixing ${fixCount} keys for lang ${lang}`);
        const dataToWrite = JSON.stringify(langObj, null, 4);
        let writeResult = await fs.writeFile(`data/i18n/${lang}.json`, dataToWrite, {
            encoding: "utf8",
            flag: "w",
            mode: 0o666
        },
        (err) => {
            console.log(err);
        });
        if(writeResult === undefined) {
            console.log(`Successfully updated file data/i18n/${lang}.json with new contents`);
        }
    }
}

const checkModelKeys = async (anniversaryEvents) => {
    let errorCount = 0;
    let modelFixCount = 0;
    let count = 0;
    const modelDataFile = await fs.readFile(`data/model.json`, 'utf8');
    const modelData = JSON.parse(modelDataFile);
    anniversaryEvents.forEach(event => {
        const key = `${event.event_key}_${event.event_idx}`;
        if(false === modelData.hasOwnProperty(key)) {
            console.log(`modelData does not have key ${key}`);
            errorCount++;
            modelFixCount++;
            modelData[key] = baseObj;
        }
        count++;
    });

    console.log(`${count} keys were verified, ${errorCount} errors found; modelData has ${Object.keys(modelData).length} keys`);
    if (modelFixCount > 0) {
        console.log(`Fixing ${modelFixCount} keys for modelData`);
        const dataToWrite = JSON.stringify(modelData, null, 4);
        let writeResult = await fs.writeFile(`data/model.json`, dataToWrite, {
            encoding: "utf8",
            flag: "w",
            mode: 0o666
        },
        (err) => {
            console.log(err);
        });
        if(writeResult === undefined) {
            console.log(`Successfully updated file data/model.json with new contents`);
        }
    }
}

readSourceData();
