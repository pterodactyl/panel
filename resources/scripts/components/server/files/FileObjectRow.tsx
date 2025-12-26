import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faFileAlt, faFileArchive, faFileImport, faFolder, faImage } from '@fortawesome/free-solid-svg-icons';
import { encodePathSegments } from '@/helpers';
import { differenceInHours, format, formatDistanceToNow } from 'date-fns';
import React, { memo, useState } from 'react';
import { FileObject } from '@/api/server/files/loadDirectory';
import FileDropdownMenu from '@/components/server/files/FileDropdownMenu';
import { ServerContext } from '@/state/server';
import { NavLink, useRouteMatch } from 'react-router-dom';
import tw from 'twin.macro';
import isEqual from "react-fast-compare";
import SelectFileCheckbox from "@/components/server/files/SelectFileCheckbox";
import { usePermissions } from "@/plugins/usePermissions";
import { join } from "pathe";
import { bytesToString } from "@/lib/formatters";
import styles from "./style.module.css";
import ImagePreviewModal from "@/components/server/files/ImagePreviewModal";

const IMAGE_EXTENSIONS = [
  "png",
  "jpg",
  "jpeg",
  "gif",
  "webp",
];

const isImageFile = (filename: string): boolean => {
  const ext = filename.split(".").pop()?.toLowerCase();
  return ext ? IMAGE_EXTENSIONS.includes(ext) : false;
};

const Clickable: React.FC<{ file: FileObject; onImageClick?: () => void }> =
  memo(({ file, children, onImageClick }) => {
    const [canRead] = usePermissions(["file.read"]);
    const [canReadContents] = usePermissions(["file.read-content"]);
    const directory = ServerContext.useStoreState(
      (state) => state.files.directory
    );

    const match = useRouteMatch();

    const isImage =
      file.isFile && isImageFile(file.name) && canReadContents && onImageClick;
    const canClick =
      (file.isFile && file.isEditable() && canReadContents) ||
      (!file.isFile && canRead) ||
      isImage;

    return canClick ? (
      <NavLink
        className={styles.details}
        to={`${match.url}${file.isFile ? "/edit" : ""}#${encodePathSegments(
          join(directory, file.name)
        )}`}
        onClick={
          isImage
            ? (e) => {
                e.preventDefault();
                onImageClick();
              }
            : undefined
        }
      >
        {children}
      </NavLink>
    ) : (
      <div className={styles.details}>{children}</div>
    );
  }, isEqual);

const FileObjectRow = ({ file }: { file: FileObject }) => {
  const [showImageModal, setShowImageModal] = useState(false);
  const directory = ServerContext.useStoreState(
    (state) => state.files.directory
  );
  const fullPath = join(directory, file.name);
  const isImage = file.isFile && isImageFile(file.name);

  return (
    <>
      <div
        className={styles.file_row}
        key={file.name}
        onContextMenu={(e) => {
          e.preventDefault();
          window.dispatchEvent(
            new CustomEvent(`pterodactyl:files:ctx:${file.key}`, {
              detail: e.clientX,
            })
          );
        }}
      >
        <SelectFileCheckbox name={file.name} />
        <Clickable
          file={file}
          onImageClick={isImage ? () => setShowImageModal(true) : undefined}
        >
          <div css={tw`flex-none text-neutral-400 ml-6 mr-4 text-lg pl-3`}>
            {file.isFile ? (
              <FontAwesomeIcon
                icon={
                  isImage
                    ? faImage
                    : file.isSymlink
                    ? faFileImport
                    : file.isArchiveType()
                    ? faFileArchive
                    : faFileAlt
                }
              />
            ) : (
              <FontAwesomeIcon icon={faFolder} />
            )}
          </div>
          <div css={tw`flex-1 truncate`}>{file.name}</div>
          {file.isFile && (
            <div css={tw`w-1/6 text-right mr-4 hidden sm:block`}>
              {bytesToString(file.size)}
            </div>
          )}
          <div
            css={tw`w-1/5 text-right mr-4 hidden md:block`}
            title={file.modifiedAt.toString()}
          >
            {Math.abs(differenceInHours(file.modifiedAt, new Date())) > 48
              ? format(file.modifiedAt, "MMM do, yyyy h:mma")
              : formatDistanceToNow(file.modifiedAt, { addSuffix: true })}
          </div>
        </Clickable>
        <FileDropdownMenu file={file} />
      </div>

      {isImage && (
        <ImagePreviewModal
          open={showImageModal}
          onClose={() => setShowImageModal(false)}
          file={fullPath}
        />
      )}
    </>
  );
};

export default memo(FileObjectRow, (prevProps, nextProps) => {
  /* eslint-disable @typescript-eslint/no-unused-vars */
  const { isArchiveType, isEditable, ...prevFile } = prevProps.file;
  const {
    isArchiveType: nextIsArchiveType,
    isEditable: nextIsEditable,
    ...nextFile
  } = nextProps.file;
  /* eslint-enable @typescript-eslint/no-unused-vars */

  return isEqual(prevFile, nextFile);
});
