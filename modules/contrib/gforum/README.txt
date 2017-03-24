### Group Forum

The Group Node module (a submodule of the Group module) supports permission
control for group forum nodes.  The Group Forum module adds the ability to make
forum containers and forums private to a group or groups.

When you associate a forum container with a group, all forums and subforums
within that container will be private to the group.

Note that topic nodes themselves are still under the purview of the Group Node
module. It will be possible for a user to access a node -- if it is their
group content and you allow access via their Group Node permissions -- even if
they can't access the forum in which the node appears.

The Group Node module is not a dependency, but it is expected that in most use
cases Group Forum will be used in conjunction with Group Node, allowing the site
builder to control both forum topic permissions and access to forum containers.

# Install

You must create a taxonomy reference field in your group type.  By default,
the Group Forum module expects this field will be named
'field_forum_containers'.  You can override this default field name in the
module's config settings if you need to use something different.

You will use the taxonomy reference field to associate each of your groups with
one or more forum containers.  Configure the field to be unlimited to allow
multiple forum containers per group.
