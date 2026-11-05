import { PanelBody, SelectControl } from "@wordpress/components";
import { useSelect } from "@wordpress/data";
import { __ } from "@wordpress/i18n";

import { ARCHIVES_POST_TYPE_ATTRIBUTE } from "./archives-constants";

type PostTypeControlProps = {
  attributes: Record<string, unknown>;
  setAttributes: (attributes: Record<string, unknown>) => void;
};

type PostTypeOption = {
  label: string;
  value: string;
};

export const PostTypeControl = ({
  attributes,
  setAttributes,
}: PostTypeControlProps) => {
  const postTypeOptions = useSelect((select: any) => {
    const types = select("core").getPostTypes({ per_page: -1 }) ?? [];

    const options: PostTypeOption[] = types
      .filter(
        (type: { viewable?: boolean; slug?: string }) =>
          type.viewable &&
          type.slug !== "attachment" &&
          !type.slug?.startsWith("wp_"),
      )
      .map((type: { labels?: { singular_name?: string }; slug?: string }) => ({
        label: type.labels?.singular_name || type.slug || "",
        value: type.slug || "",
      }))
      .sort((a: PostTypeOption, b: PostTypeOption) =>
        a.label.localeCompare(b.label),
      );

    if (!options.some((option) => option.value === "post")) {
      options.unshift({
        label: __("Post", "block-archives-post-type"),
        value: "post",
      });
    }

    return options;
  }, []);

  const postType =
    typeof attributes[ARCHIVES_POST_TYPE_ATTRIBUTE] === "string"
      ? attributes[ARCHIVES_POST_TYPE_ATTRIBUTE]
      : "post";

  return (
    <PanelBody title={__("Archive settings", "block-archives-post-type")}>
      <SelectControl
        label={__("Post type", "block-archives-post-type")}
        value={postType}
        options={postTypeOptions}
        onChange={(value: string) =>
          setAttributes({
            [ARCHIVES_POST_TYPE_ATTRIBUTE]: value || "post",
          })
        }
        help={__(
          "Which content type the date archive links should list.",
          "block-archives-post-type",
        )}
      />
    </PanelBody>
  );
};
