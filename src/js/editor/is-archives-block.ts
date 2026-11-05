import { ARCHIVES_BLOCK_NAME } from "./archives-constants";

export const isArchivesBlock = (props: { name?: string }) => {
  return props.name === ARCHIVES_BLOCK_NAME;
};
