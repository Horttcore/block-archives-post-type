import { addFilter } from "@wordpress/hooks";

import {
  ARCHIVES_BLOCK_NAME,
  ARCHIVES_POST_TYPE_ATTRIBUTE,
} from "./archives-constants";

addFilter(
  "blocks.registerBlockType",
  "block-archives-post-type/attribute",
  (settings: Record<string, any>, name: string) => {
    if (name !== ARCHIVES_BLOCK_NAME) {
      return settings;
    }

    return {
      ...settings,
      attributes: {
        ...settings.attributes,
        [ARCHIVES_POST_TYPE_ATTRIBUTE]: {
          type: "string",
          default: "post",
        },
      },
    };
  },
);
