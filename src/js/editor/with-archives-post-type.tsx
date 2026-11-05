import { InspectorControls } from "@wordpress/block-editor";
import { addFilter } from "@wordpress/hooks";

import { isArchivesBlock } from "./is-archives-block";
import { PostTypeControl } from "./post-type-control";

export const withArchivesPostType = (BlockEdit: any) => (props: any) => {
  return isArchivesBlock(props) ? (
    <>
      <BlockEdit key="edit" {...props} />
      <InspectorControls>
        <PostTypeControl {...props} />
      </InspectorControls>
    </>
  ) : (
    <BlockEdit key="edit" {...props} />
  );
};

addFilter(
  "editor.BlockEdit",
  "block-archives-post-type",
  withArchivesPostType,
);
